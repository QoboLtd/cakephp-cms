<?php
namespace Cms\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cms\Model\Entity\Article;

/**
 * Articles Model
 *
 */
class ArticlesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('articles');
        $this->displayField('title');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');
        $this->hasMany('ArticleFeaturedImages', [
            'className' => 'Cms.ArticleFeaturedImages',
            'foreignKey' => 'foreign_key',
            'conditions' => [
                'ArticleFeaturedImages.model' => 'ArticleFeaturedImage'
            ]
        ]);
        $this->hasMany('ContentImages', [
            'className' => 'Cms.ContentImages',
            'foreignKey' => 'foreign_key',
            'conditions' => [
                'ContentImages.model' => 'ContentImage'
            ]
        ]);
        $this->belongsToMany('Categories', [
            'foreignKey' => 'article_id',
            'targetForeignKey' => 'category_id',
            'joinTable' => 'articles_categories',
            'className' => 'Cms.Categories'
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
            ->requirePresence('title', 'create')
            ->notEmpty('title');

        $validator
            ->notEmpty('slug')
            ->add('slug', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->requirePresence('excerpt', 'create')
            ->notEmpty('excerpt');

        $validator
            ->requirePresence('content', 'create')
            ->notEmpty('content');

        $validator
            ->requirePresence('categories', 'create')
            ->notEmpty('categories');

        $validator
            ->dateTime('publish_date')
            ->requirePresence('publish_date', 'create')
            ->notEmpty('publish_date');

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
     * Reusable Query that return articles with the latest associated image.
     *
     * @param  Query  $query   To proceess it
     * @param  array  $options Extra options can be passed.
     * @return Query  $query   Return the query object which can be chained as usual.
     */
    public function findWithLatestImage(Query $query, array $options)
    {
        $query = $query
            ->find('all')
            ->contain([
                'Categories',
                'ArticleFeaturedImages' => ['sort' => ['created' => 'DESC']]
            ]);
        if (isset($options['id'])) {
            $query = $query
                ->where(['id' => $options['id']])
                ->first();
        }
        return $query;
    }

    /**
     * Reusable query to find article per given category.
     * Also, related featured images can be provided by sending the corresponding option.
     *
     * By default, `$options` will recognize the following keys:
     *
     * - category
     * - featuredImage
     *    - Could be either bool or array
     *
     * @param  Query  $query   Raw query object
     * @param  array  $options Set of options
     * @return Query  $query   Manipulated query object
     */
    public function findByCategory(Query $query, array $options)
    {
        $associated = [];

        if (empty($options['category'])) {
            return false;
        }

        if (!empty($options['featuredImage'])) {
            if ($options['featuredImage'] === true) {
                //Default options
                $defaultOptions = ['sort' => ['created' => 'DESC']];
                $associated = ['ArticleFeaturedImages' => $defaultOptions];
            }

            if (is_array($options['featuredImage'])) {
                //Given options
                $associated = ['ArticleFeaturedImages' => $options['featuredImage']];
            }
        }

        $query->find('all');
        $query->order(['Articles.created' => 'desc']);
        $query->contain($associated);
        $query->matching('Categories', function ($q) use ($options) {
            return $q->where(['Categories.slug' => $options['category']]);
        });

        return $query;
    }


    public function beforeRules(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $slug = Inflector::slug(strtolower($entity->title));
        $notfound = false;
        $i = 0;
        do {
            if ($this->exists(['slug' => $slug])) {
                // First iteration.
                if (!$i) {
                    $slug .= '-';
                }
                $i++;
                $slug = substr($slug, 0, strrpos($slug, '-')) . '-' . $i;
            } else {
                $notfound = true;
            }
        } while (!$notfound);

        $entity->slug = $slug;
    }
}
