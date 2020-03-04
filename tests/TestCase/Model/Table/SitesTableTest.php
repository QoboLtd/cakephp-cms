<?php
namespace Cms\Test\TestCase\Model\Table;

use Cake\ORM\Association\HasMany;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Cms\Model\Entity\Site;
use Cms\Model\Table\SitesTable;

/**
 * Cms\Model\Table\SitesTable Test Case
 */
class SitesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Cms\Model\Table\SitesTable
     */
    public $Sites;

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
        $config = TableRegistry::exists('Sites') ? [] : ['className' => 'Cms\Model\Table\SitesTable'];
        /**
         * @var \Cms\Model\Table\SitesTable $table
         */
        $table = TableRegistry::get('Sites', $config);
        $this->Sites = $table;
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Sites);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertTrue($this->Sites->hasBehavior('Timestamp'));
        $this->assertTrue($this->Sites->hasBehavior('Slug'));
        $this->assertTrue($this->Sites->hasBehavior('Trash'));
        $this->assertInstanceOf(HasMany::class, $this->Sites->getAssociation('Categories'));
        $this->assertInstanceOf(HasMany::class, $this->Sites->getAssociation('Articles'));
        $this->assertInstanceOf(SitesTable::class, $this->Sites);
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $data = ['name' => 'Foo bar', 'active' => true];
        $entity = $this->Sites->newEntity();
        $entity = $this->Sites->patchEntity($entity, $data);
        /**
         * @var \Cms\Model\Entity\Site $entity
         */
        $entity = $this->Sites->save($entity);

        $this->assertNotEmpty($entity->get('id'));
        $this->assertEquals('foo-bar', $entity->get('slug'));

        $this->assertInstanceOf(Validator::class, $this->Sites->validationDefault(new Validator()));
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->assertInstanceOf(RulesChecker::class, $this->Sites->buildRules(new RulesChecker()));
    }

    public function testGetSite(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $entity = $this->Sites->getSite($id);
        $this->assertInstanceOf(Site::class, $entity);
        $this->assertEquals($id, $entity->get('id'));
        $this->assertNull($entity->get('articles'));
        $this->assertNull($entity->get('categories'));
    }

    public function testGetSiteWithCategories(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $entity = $this->Sites->getSite($id, true);
        $this->assertNull($entity->get('articles'));

        $categories = $entity->get('categories');
        $this->assertInternalType('array', $categories);
        $this->assertNotEmpty($categories);
    }

    public function testGetSiteWithArticles(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $entity = $this->Sites->getSite($id, false, true);
        $this->assertNull($entity->get('categories'));

        $articles = $entity->get('articles');
        $this->assertInternalType('array', $articles);
        $this->assertNotEmpty($articles);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetSiteEmptyParameter(): void
    {
        $this->Sites->getSite('');
    }
}
