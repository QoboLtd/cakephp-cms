<?php
namespace Cms\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;
use Cms\Model\Entity\Category;
use Cms\Model\Entity\Site;

/**
 * @property \Cms\Model\Table\CategoriesTable $Categories
 * @property \Cms\Model\Table\ArticleFeaturedImagesTable $ArticleFeaturedImages
 */
class CategoriesControllerTest extends IntegrationTestCase
{
    /**
     * Category table
     *
     * @var \Cms\Model\Table\CategoriesTable $Categories
     */
    public $Categories;

    public $fixtures = [
        'plugin.Cms.Categories',
        'plugin.Cms.Articles',
        'plugin.Cms.Sites',
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
         * @var \Cms\Model\Table\CategoriesTable $table
         */
        $table = TableRegistry::get('Cms.Categories');
        $this->Categories = $table;

        // Save featured image
        $data = [
            'foreign_key' => '00000000-0000-0000-0000-000000000001',
            'model' => 'ArticleFeaturedImage',
            'filename' => 'cake.icon.png',
        ];
        $table = $this->Categories->Articles->ArticleFeaturedImages;
        $entity = $table->newEntity();
        $entity = $table->patchEntity($entity, $data);
        $table->save($entity);
    }

    public function tearDown()
    {
        unset($this->Categories);

        parent::tearDown();
    }

    public function testView(): void
    {
        $this->get('/cms/site/blog/category/general/view');
        $this->assertResponseOk();

        $this->assertInstanceOf(Category::class, $this->viewVariable('category'));
        $this->assertInstanceOf(Site::class, $this->viewVariable('site'));
        $this->assertInternalType('array', $this->viewVariable('category')->get('articles'));
        $this->assertInternalType('array', $this->viewVariable('site')->get('categories'));
        $this->assertInternalType('array', $this->viewVariable('categories'));
        $this->assertInternalType('array', $this->viewVariable('filteredCategories'));
    }

    public function testAdd(): void
    {
        $data = [
            'name' => 'Category 7',
        ];
        $this->post('/cms/site/blog/categories/add', $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');
        $this->assertSession('The category has been saved.', 'Flash.flash.0.message');
    }

    public function testAddValidationError(): void
    {
        $data = [
            // 'name' => 'News',
        ];
        $this->post('/cms/site/blog/categories/add', $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');
        $this->assertSession('The category could not be saved. Please, try again.', 'Flash.flash.0.message');
    }

    /**
     * @dataProvider idsProvider
     */
    public function testEdit(string $id): void
    {
        $data = ['name' => 'Category foobar'];
        $this->put('/cms/site/blog/categories/edit/' . $id, $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');
        $this->assertSession('The category has been saved.', 'Flash.flash.0.message');
        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $entity = $this->Categories->find()
            ->where(['OR' => ['id' => $id, 'slug' => $id]])
            ->first();
        $this->assertEquals($data['name'], $entity->get('name'));
    }

    /**
     * @dataProvider idsProvider
     */
    public function testDelete(string $id): void
    {
        $this->delete('/cms/site/blog/categories/delete/' . $id);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');

        $query = $this->Categories->find()->where(['id' => $id]);

        $this->assertTrue($query->isEmpty());
    }

    public function testDeleteInvalidId(): void
    {
        $id = '00000000-0000-0000-0000-000000000404';
        $this->delete('/cms/site/blog/categories/delete/' . $id);

        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    /**
     * @dataProvider idsProvider
     */
    public function testMoveNode(string $id, string $action): void
    {
        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $result = $this->Categories->find()
            ->where(['OR' => ['id' => $id, 'slug' => $id]])
            ->first();

        $this->delete('/cms/site/blog/categories/moveNode/' . $id . '/' . $action);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');

        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $entity = $this->Categories->find()
            ->where(['OR' => ['id' => $id, 'slug' => $id]])
            ->first();

        $this->assertNotEquals($result->get('lft'), $entity->get('lft'));
        $this->assertNotEquals($result->get('rght'), $entity->get('rght'));
    }

    public function testMoveNodeInvalidAction(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';
        $action = 'foobar';
        $this->delete('/cms/site/blog/categories/moveNode/' . $id . '/' . $action);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');
        $this->assertSession('Unknown move action.', 'Flash.flash.0.message');
    }

    /**
     * @return mixed[]
     */
    public function idsProvider(): array
    {
        return [
            ['00000000-0000-0000-0000-000000000001', 'down'],
            ['general', 'down'],
            ['00000000-0000-0000-0000-000000000002', 'up'],
            ['news', 'up'],
        ];
    }
}
