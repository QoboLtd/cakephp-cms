<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cms\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;

/**
 * Categories Model
 *
 * @property \Cake\ORM\Association\BelongsTo $ParentCategories
 * @property \Cake\ORM\Association\HasMany $ChildCategories
 * @property \Cms\Model\Table\ArticlesTable $Articles
 * @property \Cms\Model\Table\SitesTable $Sites
 *
 * @mixin \Muffin\Slug\Model\Behavior\SlugBehavior
 *
 */
class CategoriesTable extends Table
{
    const TREE_SPACER = '&nbsp;&nbsp;&nbsp;&nbsp;';

    /**
     * Categories in a tree list structure, grouped by site.
     *
     * @var array
     */
    protected $_treeList = [];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('qobo_cms_categories');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Tree');
        $this->addBehavior('Muffin/Slug.Slug', [
            'unique' => function (EntityInterface $entity, $slug, $separator) {
                return $this->_uniqueSlug($entity, $slug, $separator);
            },
        ]);

        $this->belongsTo('Cms.Sites');
        $this->belongsTo('ParentCategories', [
            'className' => 'Cms.Categories',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('ChildCategories', [
            'className' => 'Cms.Categories',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('Cms.Articles', [
            'foreignKey' => 'category_id',
            'sort' => ['Articles.publish_date' => 'DESC'],
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->uuid('id')
            ->allowEmpty('id', 'create');

        $validator
            ->notEmpty('slug');

        $validator
            ->requirePresence('name', 'create')
            ->notEmpty('name');

        $validator
            ->requirePresence('site_id', 'create')
            ->notEmpty('site_id');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['parent_id'], 'ParentCategories'));

        return $rules;
    }

    /**
     * Fetch and return Category by id or slug and associated Site id.
     *
     * @param string|null $id Category id or slug.
     * @param \Cake\Datasource\EntityInterface $site Site entity.
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     * @throws \InvalidArgumentException
     *
     * @return \Cake\Datasource\EntityInterface
     */
    public function getBySite(?string $id, EntityInterface $site): EntityInterface
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Category id or slug cannot be empty.');
        }

        if (!is_string($id)) {
            throw new InvalidArgumentException('Category id or slug must be a string.');
        }

        $query = $this->find('all')
            ->where([
                'AND' => [
                    'Categories.site_id' => $site->id,
                    'OR' => [
                        'Categories.id' => $id,
                        'Categories.slug' => $id,
                    ],
                ],
            ])
            ->limit(1);
        $query->enableHydration(true);

        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $result = $query->firstOrFail();

        return $result;
    }

    /**
     * Get tree list structure of site's categories.
     *
     * @param string $siteId Site Id.
     * @param string $categoryId Site Id.
     * @param bool $filteredArticles flag in order to display a category if there is at least one article under this category.
     *
     * @return mixed[]
     */
    public function getTreeList(string $siteId = '', string $categoryId = '', bool $filteredArticles = false): array
    {
        $key = $siteId . $categoryId;

        if ($filteredArticles) {
            $key = $key . '_filtered';
        }

        if (!empty($this->_treeList[$key])) {
            return $this->_treeList[$key];
        }

        $conditions = [];
        if ($siteId) {
            $conditions['Categories.site_id'] = $siteId;
        }
        if ($categoryId) {
            $conditions['Categories.id !='] = $categoryId;
        }

        $query = $this->find('treeList', [
                'conditions' => $conditions,
                'spacer' => static::TREE_SPACER,
            ]);

        if ($filteredArticles) {
            $query->innerJoinWith('Articles', function ($q) {
                return $q;
            });
        }

        $this->_treeList[$key] = $query->toArray();

        return $this->_treeList[$key];
    }

    /**
     * Returns a unique slug.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param string $slug Slug.
     * @param string|null $separator Separator.
     *
     * @return string Unique slug.
     */
    protected function _uniqueSlug(EntityInterface $entity, string $slug, ?string $separator = null): string
    {
        $behavior = $this->getBehavior('Slug');

        /**
         * @var string
         */
        $primaryKey = $this->getPrimaryKey();
        $field = $this->aliasField($behavior->getConfig('field'));

        $conditions = [$field => $slug];
        // add site id to conditions
        $conditions['Categories.site_id'] = $entity->get('site_id');
        $conditions += $behavior->getConfig('scope');
        if ($id = $entity->{$primaryKey}) {
            $conditions['NOT'][$this->aliasField($primaryKey)] = $id;
        }

        $i = 0;
        $suffix = '';
        $length = $behavior->getConfig('maxLength');

        while ($this->exists($conditions)) {
            $i++;
            $suffix = $separator . $i;
            if ($length && $length < mb_strlen($slug . $suffix)) {
                $slug = mb_substr($slug, 0, $length - mb_strlen($suffix));
            }
            $conditions[$field] = $slug . $suffix;
        }

        return $slug . $suffix;
    }
}
