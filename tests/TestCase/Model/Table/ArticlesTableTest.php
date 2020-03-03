<?php
namespace Cms\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\HasMany;
use Cake\ORM\ResultSet;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Cms\Model\Entity\Article;
use Cms\Model\Entity\Category;
use Cms\Model\Table\ArticlesTable;

/**
 * Cms\Model\Table\ArticlesTable Test Case
 */
class ArticlesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Cms\Model\Table\ArticlesTable
     */
    public $Articles;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Cms.Articles',
        'plugin.Cms.Categories',
        'plugin.Cms.Sites',
        'plugin.Cms.FileStorage',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Articles') ? [] : ['className' => 'Cms\Model\Table\ArticlesTable'];
        /**
         * @var \Cms\Model\Table\ArticlesTable $table
         */
        $table = TableRegistry::get('Articles', $config);
        $this->Articles = $table;

        // load default plugin config
        Configure::load('Cms.cms');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Articles);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertEquals('qobo_cms_articles', $this->Articles->getTable());

        $this->assertEquals('title', $this->Articles->getDisplayField());
        $this->assertEquals('id', $this->Articles->getPrimaryKey());

        $this->assertTrue($this->Articles->hasBehavior('Timestamp'));
        $this->assertTrue($this->Articles->hasBehavior('Trash'));
        $this->assertTrue($this->Articles->hasBehavior('Slug'));

        $this->assertInstanceOf(HasMany::class, $this->Articles->getAssociation('ArticleFeaturedImages'));
        $this->assertInstanceOf(BelongsTo::class, $this->Articles->getAssociation('Sites'));
        $this->assertInstanceOf(BelongsTo::class, $this->Articles->getAssociation('Categories'));
        $this->assertInstanceOf(BelongsTo::class, $this->Articles->getAssociation('Author'));
        $this->assertInstanceOf(BelongsTo::class, $this->Articles->getAssociation('Editor'));

        $this->assertInstanceOf(ArticlesTable::class, $this->Articles);
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $data = [
            'title' => 'Foo bar',
            'content' => 'Lorem ipsum...',
            'category_id' => '00000000-0000-0000-0000-000000000001',
            'publish_date' => '2017-04-11 10:00:38',
            'site_id' => '00000000-0000-0000-0000-000000000001',
            'type' => 'article',
            'excerpt' => '',
            'created_by' => '00000000-0000-0000-0000-000000000001',
            'modified_by' => '00000000-0000-0000-0000-000000000002',
        ];
        $entity = $this->Articles->newEntity();
        $entity = $this->Articles->patchEntity($entity, $data);

        $this->Articles->save($entity);

        $this->assertNotEmpty($entity->get('id'));
        $this->assertEquals('foo-bar', $entity->get('slug'));

        $this->assertInstanceOf(Validator::class, $this->Articles->validationDefault(new Validator()));
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->assertInstanceOf(RulesChecker::class, $this->Articles->buildRules(new RulesChecker()));
    }

    public function testSaveWithShortcode(): void
    {
        $data = [
            'title' => 'An Article with a Shortcode',
            'content' => 'Some content with a [shortcode]',
            'publish_date' => '2017-04-11 10:00:38',
            'category_id' => '00000000-0000-0000-0000-000000000001',
            'site_id' => '00000000-0000-0000-0000-000000000001',
            'excerpt' => '',
            'created_by' => '162deb54-dc40-4967-b6d2-451c371fdb2d',
            'modified_by' => '63be1f3e-3628-49c7-9f6b-1a7013e154f4',
            'type' => 'article',
        ];

        $entity = $this->Articles->newEntity();
        $entity = $this->Articles->patchEntity($entity, $data);

        $saved = $this->Articles->save($entity);
        $this->assertInstanceOf(Article::class, $saved);
    }

    public function testGetTypes(): void
    {
        $result = $this->Articles->getTypes();

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('article', $result);
    }

    public function testGetTypesDoubleCall(): void
    {
        $this->Articles->getTypes();
        $result = $this->Articles->getTypes();

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('article', $result);
    }

    public function testGetTypesWithDisabledType(): void
    {
        Configure::write('CMS.Articles.types.article.enabled', false);
        $result = $this->Articles->getTypes();

        $this->assertArrayNotHasKey('article', $result);
    }

    public function testGetTypesWithoutOptions(): void
    {
        $result = $this->Articles->getTypes(false);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertContains('article', $result);
    }

    public function testGetOptions(): void
    {
        $result = $this->Articles->getTypeOptions('article');

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('fields', $result);
    }

    public function testGetOptionsWithWrongType(): void
    {
        $result = $this->Articles->getTypeOptions('foobar');

        $this->assertEmpty($result);
    }

    public function testSetSearchQuery(): void
    {
        $this->assertEquals('', $this->Articles->getSearchQuery());

        $this->Articles->setSearchQuery('foo');
        $this->assertEquals('foo', $this->Articles->getSearchQuery());
    }

    public function testApplySearch(): void
    {
        $query = $this->Articles->find();
        $expected = clone $query;

        $this->Articles->applySearch($query, 'foo');

        $this->assertNotEquals($expected, $query);
    }

    public function testApplySearchWithoutQuery(): void
    {
        $query = $this->Articles->find();
        $expected = clone $query;

        $this->Articles->applySearch($query, '');

        $this->assertEquals($expected, $query);
    }

    public function testGetArticle(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $entity = $this->Articles->getArticle($id, null);

        $this->assertInstanceOf(Article::class, $entity);
        $this->assertNull($entity->get('category'));
        $this->assertNull($entity->get('article_featured_images'));
    }

    public function testGetArticleWithAssociated(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $entity = $this->Articles->getArticle($id, null, true);

        $this->assertInstanceOf(Article::class, $entity);
        $this->assertInstanceOf(Category::class, $entity->get('category'));
        $this->assertInternalType('array', $entity->get('article_featured_images'));
    }

    public function testGetArticles(): void
    {
        $siteId = '00000000-0000-0000-0000-000000000001';
        $type = 'article';

        $entities = $this->Articles->getArticles($siteId, $type);

        $this->assertInstanceOf(ResultSet::class, $entities);
        $this->assertFalse($entities->isEmpty());

        foreach ($entities as $entity) {
            $this->assertInstanceOf(Article::class, $entity);
            $this->assertNull($entity->get('category'));
            $this->assertNull($entity->get('article_featured_images'));
        }
    }

    public function testGetArticlesWithAssociated(): void
    {
        $siteId = '00000000-0000-0000-0000-000000000001';
        $type = 'article';

        $entities = $this->Articles->getArticles($siteId, $type, true);

        $this->assertInstanceOf(ResultSet::class, $entities);
        $this->assertFalse($entities->isEmpty());

        foreach ($entities as $entity) {
            $this->assertInstanceOf(Article::class, $entity);
            $this->assertInstanceOf(Category::class, $entity->get('category'));
            $this->assertInternalType('array', $entity->get('article_featured_images'));
        }
    }

    public function testGetArticlesByCategory(): void
    {
        $ids = [
            '00000000-0000-0000-0000-000000000001',
            '00000000-0000-0000-0000-000000000002',
        ];

        $entities = $this->Articles->getArticlesByCategory($ids);

        $this->assertInstanceOf(ResultSet::class, $entities);
        $this->assertFalse($entities->isEmpty());

        foreach ($entities as $entity) {
            $this->assertInstanceOf(Article::class, $entity);
        }
    }

    public function testUniqueSlug(): void
    {
        $data = [
            'title' => 'First Article',
            'excerpt' => 'Lorem ipsum',
            'content' => 'Lorem ipsum dolor sit amet',
            'site_id' => '00000000-0000-0000-0000-000000000001',
            'category_id' => '00000000-0000-0000-0000-000000000001',
            'type' => 'article',
            'publish_date' => '2017-10-05 18:30:00',
            'created_by' => '00000000-0000-0000-0000-000000000001',
            'modified_by' => '00000000-0000-0000-0000-000000000001',
        ];

        $entity = $this->Articles->newEntity();
        $entity = $this->Articles->patchEntity($entity, $data);

        $this->Articles->save($entity);

        $this->assertEquals('first-article-1', $entity->get('slug'));
    }
}
