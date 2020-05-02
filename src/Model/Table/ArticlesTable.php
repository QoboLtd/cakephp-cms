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

use ArrayObject;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cms\Event\EventName;
use Cms\View\Shortcode;
use InvalidArgumentException;

/**
 * Articles Model
 *
 * @property \Cms\Model\Table\ArticleFeaturedImagesTable $ArticleFeaturedImages
 * @property \Cms\Model\Table\SitesTable $Sites
 * @property \Cms\Model\Table\CategoriesTable $Categories
 * @property \Muffin\Slug\Model\Behavior\SlugBehavior $Slug
 */
class ArticlesTable extends Table
{
    /**
     * Article types list.
     *
     * @var array
     */
    protected $_types = [];

    /**
     * Type fields default.
     *
     * @var array
     */
    protected $_fieldDefaults = [
        'renderAs' => 'text',
        'required' => true,
        'editor' => false,
    ];

    /**
     * Search query string.
     *
     * @var string
     */
    protected $_searchQuery = '';

    /**
     * Article searchable fields.
     *
     * @var mixed[]
     */
    protected $_searchableFields = ['title', 'excerpt', 'content'];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('qobo_cms_articles');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');
        $this->addBehavior('Muffin/Slug.Slug', [
            'unique' => function (EntityInterface $entity, $slug, $separator) {
                return $this->_uniqueSlug($entity, $slug, $separator);
            },
        ]);

        $this->hasMany('ArticleFeaturedImages', [
            'className' => 'Cms.ArticleFeaturedImages',
            'foreignKey' => 'foreign_key',
            'conditions' => [
                'ArticleFeaturedImages.model' => 'ArticleFeaturedImage',
            ],
            'sort' => ['ArticleFeaturedImages.created' => 'DESC'],
        ]);
        $this->belongsTo('Cms.Sites');
        $this->belongsTo('Cms.Categories');
        $this->belongsTo('Author', [
            'className' => 'CakeDC/Users.Users',
            'foreignKey' => 'created_by',
        ]);

        $this->belongsTo('Editor', [
            'className' => 'CakeDC/Users.Users',
            'foreignKey' => 'modified_by',
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->notEmptyString('slug')
            ->add('slug', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->requirePresence('content', 'create')
            ->allowEmptyString('content');

        $validator
            ->requirePresence('category_id', 'create')
            ->notEmptyString('category_id');

        $validator
            ->dateTime('publish_date')
            ->requirePresence('publish_date', 'create')
            ->notEmptyDateTime('publish_date');

        $validator
            ->requirePresence('site_id', 'create')
            ->notEmptyString('site_id');

        $validator
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

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
        $rules->add($rules->isUnique(['slug']));

        return $rules;
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function beforeFind(Event $event, Query $query, ArrayObject $options): void
    {
        $siteId = !empty($options['site_id']) ? $options['site_id'] : '';

        $event = new Event((string)EventName::ARTICLES_SHOW_UNPUBLISHED(), $this, ['siteId' => $siteId]);
        $this->getEventManager()->dispatch($event);

        $query->order(['Articles.publish_date' => 'DESC']);
        if (!(bool)$event->result) {
            $query->where(['Articles.publish_date <=' => Time::now()]);
        }

        $searchQuery = $this->getSearchQuery();

        if (!empty($searchQuery)) {
            $this->applySearch($query, $searchQuery);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        $types = $this->getTypes($entity->get('type'));
        $fields = $types[$entity->get('type')]['fields'];

        foreach ($fields as $info) {
            // skip empty values
            if (!$entity->get($info['field'])) {
                continue;
            }

            // skip non-editor fields
            if (!$info['editor']) {
                continue;
            }

            $shortcodes = Shortcode::get($entity->get($info['field']));
            if (empty($shortcodes)) {
                continue;
            }

            foreach ($shortcodes as $shortcode) {
                $encoded = json_encode($shortcode);
                if (!$encoded) {
                    continue;
                }

                $cacheKey = 'shortcode_' . md5($encoded);
                // delete shortcode cache
                Cache::delete($cacheKey);
            }
        }
    }

    /**
     * Search query getter.
     *
     * @return string
     */
    public function getSearchQuery(): string
    {
        return $this->_searchQuery;
    }

    /**
     * Search query setter.
     *
     * @param string $searchQuery Search query string
     *
     * @return void
     */
    public function setSearchQuery(string $searchQuery): void
    {
        if (!is_string($searchQuery)) {
            throw new InvalidArgumentException('Search query must be a string.');
        }

        $this->_searchQuery = $searchQuery;
    }

    /**
     * Apply search query value to the provided Query instance.
     *
     * @param \Cake\ORM\Query $query Query instance
     * @param string $searchQuery Search query value
     *
     * @return void
     */
    public function applySearch(Query $query, string $searchQuery): void
    {
        if (empty($searchQuery)) {
            return;
        }

        $conditions = [];
        foreach ($this->_searchableFields as $field) {
            $conditions[$this->aliasField($field) . ' LIKE'] = '%' . $searchQuery . '%';
        }

        $query->where(['OR' => $conditions]);
    }

    /**
     * Fetch and return Article by id or slug.
     *
     * @param string $id Article id or slug.
     * @param string|null $siteId Site id.
     * @param bool $associated Contain associated articles and images.
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     * @throws \InvalidArgumentException
     *
     * @return \Cake\Datasource\EntityInterface
     */
    public function getArticle(string $id, ?string $siteId, bool $associated = false): EntityInterface
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Article id or slug cannot be empty.');
        }

        if (!is_string($id)) {
            throw new InvalidArgumentException('Article id or slug must be a string.');
        }

        $contain = [];
        if ($associated) {
            $contain = [
                'Categories',
                'ArticleFeaturedImages',
            ];
        }

        $query = $this->find('all')
            ->where([
                'OR' => [
                    'Articles.id' => $id,
                    'Articles.slug' => $id,
                ],
            ]);
        $query->enableHydration(true);
        $query->limit(1);
        $query->contain($contain);
        $query->applyOptions(['site_id' => $siteId]);

        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $result = $query->firstOrFail();

        return $result;
    }

    /**
     * Fetch and return all Articles, or Articles by site, or Articles by type, or Articles by site and type.
     *
     * @param string $siteId Site id.
     * @param string $type Type name.
     * @param bool $associated Contain associated categories and images.
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     * @throws \InvalidArgumentException
     *
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getArticles(string $siteId, string $type, bool $associated = false): ResultSetInterface
    {
        if (!is_string($siteId)) {
            throw new InvalidArgumentException('Site id or slug must be a string.');
        }

        if (!is_string($type)) {
            throw new InvalidArgumentException('Article type id or slug must be a string.');
        }

        $contain = [];
        if ($associated) {
            $contain = [
                'Categories',
                'ArticleFeaturedImages',
            ];
        }

        $conditions = [];
        if ($siteId) {
            $conditions['Articles.site_id'] = $siteId;
        }
        if ($type) {
            $conditions['Articles.type'] = $type;
        }

        $query = $this->find('all')
            ->where($conditions);
        $query->enableHydration(true);
        $query->contain($contain);
        $query->applyOptions(['site_id' => $siteId]);

        /**
         * @var \Cake\Datasource\ResultSetInterface
         */
        $result = $query->all();

        return $result;
    }

    /**
     * Returns supported types.
     *
     * @param bool $withOptions Flag for including type options
     *
     * @return mixed[]
     */
    public function getTypes(bool $withOptions = true): array
    {
        if (!empty($this->_types)) {
            return $this->_types;
        }

        $this->_types = Configure::read('CMS.Articles.types');
        ksort($this->_types);
        foreach ($this->_types as $k => &$v) {
            if (!(bool)$v['enabled']) {
                unset($this->_types[$k]);

                continue;
            }

            // normalize field options
            foreach ($v['fields'] as &$field) {
                $field = array_merge($this->_fieldDefaults, $field);
                // normalize options
                if (!empty($field['options'])) {
                    $options = [];
                    foreach ($field['options'] as $index => $option) {
                        $options[Inflector::underscore(Inflector::classify($option))] = $option;
                    }
                    $field['options'] = $options;
                }
            }

            // normalize label
            $v['label'] = $v['label'] ? $v['label'] : Inflector::humanize($k);
        }

        if (! $withOptions) {
            return array_keys($this->_types);
        }

        return $this->_types;
    }

    /**
     * Returns type options.
     *
     * @param string $type Type name
     * @return mixed[]
     */
    public function getTypeOptions(string $type): array
    {
        $result = [];

        $types = $this->getTypes();
        if (!array_key_exists($type, $types)) {
            return $result;
        }

        $result = $types[$type];

        return $result;
    }

    /**
     * Returns specified category(ies) articles.
     *
     * @param mixed[] $ids Category(ies) ID(s)
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function getArticlesByCategory(array $ids): ResultSetInterface
    {
        $query = $this->find('all')
            ->where(['Articles.category_id IN' => $ids]);
        $query->enableHydration(true);
        $query->contain(['ArticleFeaturedImages']);

        /**
         * @var \Cake\Datasource\ResultSetInterface
         */
        $result = $query->all();

        return $result;
    }

    /**
     * Returns a unique slug.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param string $slug Slug.
     * @param string $separator Separator.
     *
     * @return string Unique slug.
     */
    protected function _uniqueSlug(EntityInterface $entity, string $slug, string $separator): string
    {
        $behavior = $this->getBehavior('Slug');

        /**
         * @var string
         */
        $primaryKey = $this->getPrimaryKey();
        $field = $this->aliasField($behavior->getConfig('field'));

        $conditions = [$field => $slug];
        $conditions += $behavior->getConfig('scope');
        if ($id = $entity->{$primaryKey}) {
            $conditions['NOT'][$this->aliasField($primaryKey)] = $id;
        }

        $i = 0;
        $suffix = '';
        $length = $behavior->getConfig('maxLength');

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
