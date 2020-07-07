<?php
namespace Cms\Test\TestCase\Controller;

use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cms\Model\Entity\Site;

/**
 * @property \Cms\Model\Table\SitesTable $Sites
 */
class SitesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public $fixtures = [
        'plugin.Cms.Sites',
        'plugin.Cms.Articles',
        'plugin.Cms.Categories',
        'plugin.Cms.FileStorage',
    ];

    public function setUp()
    {
        parent::setUp();

        $this->session([
            'Auth' => [
                'User' => [
                    'id' => '00000000-0000-0000-0000-000000000001',
                ],
            ],
        ]);

        $this->enableRetainFlashMessages();

        /**
         * @var \Cms\Model\Table\SitesTable $table
         */
        $table = TableRegistry::getTableLocator()->get('Cms.Sites');
        $this->Sites = $table;

        // Save featured image
        $data = [
            'foreign_key' => '00000000-0000-0000-0000-000000000001',
            'model' => 'ArticleFeaturedImage',
            'filename' => 'cake.icon.png',
        ];
        $table = $this->Sites->Articles->ArticleFeaturedImages;
        $entity = $table->newEntity();
        $entity = $table->patchEntity($entity, $data);
        $table->save($entity);
    }

    public function tearDown()
    {
        unset($this->Sites);

        parent::tearDown();
    }

    public function testIndex(): void
    {
        $this->get('/cms/sites');
        $this->assertResponseOk();

        $this->assertInstanceOf(ResultSet::class, $this->viewVariable('sites'));
    }

    /**
     * @dataProvider idsProvider
     */
    public function testView(string $id): void
    {
        $this->get('/cms/sites/view/' . $id);
        $this->assertResponseOk();

        $this->assertInstanceOf(Site::class, $this->viewVariable('site'));
        $this->assertInternalType('array', $this->viewVariable('site')->get('articles'));
        $this->assertInternalType('array', $this->viewVariable('site')->get('categories'));
        $this->assertInternalType('array', $this->viewVariable('categories'));
        $this->assertInternalType('array', $this->viewVariable('filteredCategories'));
    }

    public function testAdd(): void
    {
        $data = [
            'name' => 'News',
            'active' => true,
        ];
        $this->post('/cms/sites/add', $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/cms/sites/view/news');
        $this->assertSession('The site has been saved.', 'Flash.flash.0.message');
    }

    public function testAddValidationError(): void
    {
        $data = [
            'name' => 'News',
            // 'active' => true,
        ];
        $this->post('/cms/sites/add', $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/cms/sites');
        $this->assertSession('The site could not be saved. Please, try again.', 'Flash.flash.0.message');
    }

    public function testEdit(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';
        $data = ['name' => 'Foobar'];
        $this->put('/cms/sites/edit/' . $id, $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/cms/sites/view/blog');
        $this->assertSession('The site has been saved.', 'Flash.flash.0.message');

        $entity = $this->Sites->get($id);
        $this->assertEquals($data['name'], $entity->get('name'));
    }

    public function testDelete(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';
        $this->delete('/cms/sites/delete/' . $id);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/cms/sites');

        $query = $this->Sites->find()->where(['id' => $id]);

        $this->assertTrue($query->isEmpty());
    }

    public function testDeleteInvalidId(): void
    {
        $id = '00000000-0000-0000-0000-000000000404';
        $this->delete('/cms/sites/delete/' . $id);

        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    /**
     * @return mixed[]
     */
    public function idsProvider(): array
    {
        return [
            ['00000000-0000-0000-0000-000000000001'],
            ['blog'],
        ];
    }
}
