<?php
namespace Cms\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;

/**
 * Categories Model
 *
 * @property \Cake\ORM\Association\BelongsTo $ParentCategories
 * @property \Cake\ORM\Association\HasMany $ChildCategories
 * @property \Cake\ORM\Association\BelongsToMany $Articles
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

        $this->table('categories');
        $this->displayField('name');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Tree');
        $this->addBehavior('Muffin/Slug.Slug', [
            'unique' => function (Entity $entity, $slug, $separator) {
                return $this->_uniqueSlug($entity, $slug, $separator);
            }
        ]);

        $this->belongsTo('Cms.Sites');
        $this->belongsTo('ParentCategories', [
            'className' => 'Cms.Categories',
            'foreignKey' => 'parent_id'
        ]);
        $this->hasMany('ChildCategories', [
            'className' => 'Cms.Categories',
            'foreignKey' => 'parent_id'
        ]);
        $this->hasMany('Cms.Articles', [
            'sort' => ['Articles.publish_date' => 'DESC']
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
     * @param string $id Category id or slug.
     * @param \Cake\ORM\Entity $site Site entity.
     * @return \Cake\ORM\Entity
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     * @throws \InvalidArgumentException
     */
    public function getBySite($id, Entity $site)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Category id or slug cannot be empty.');
        }

        if (!is_string($id)) {
            throw new InvalidArgumentException('Category id or slug must be a string.');
        }

        $query = $this->find('all')
            ->limit(1)
            ->where(['Categories.id' => $id])
            ->orWhere(['Categories.slug' => $id])
            ->andWhere(['Categories.site_id' => $site->id]);

        return $query->firstOrFail();
    }

    /**
     * Get tree list structure of site's categories.
     *
     * @param string $siteId Site Id.
     * @param string $categoryId Site Id.
     * @param bool $filteredArticles flag in order to display a category if there is at least one article under this category.
     *
     * @return array
     */
    public function getTreeList($siteId = '', $categoryId = '', $filteredArticles = false)
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
                'spacer' => static::TREE_SPACER
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
     * @param \Cake\ORM\Entity $entity Entity.
     * @param string $slug Slug.
     * @param string $separator Separator.
     * @return string Unique slug.
     */
    protected function _uniqueSlug(Entity $entity, $slug, $separator)
    {
        $behavior = $this->behaviors()->Slug;

        $primaryKey = $this->primaryKey();
        $field = $this->aliasField($behavior->config('field'));

        $conditions = [
            $field => $slug,
            'Categories.site_id' => $entity->site_id
        ];
        $conditions += $behavior->config('scope');
        if ($id = $entity->{$primaryKey}) {
            $conditions['NOT'][$this->_table->aliasField($primaryKey)] = $id;
        }

        $i = 0;
        $suffix = '';
        $length = $behavior->config('length');

        while (!$this->find('withTrashed', ['conditions' => $conditions])->isEmpty()) {
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
