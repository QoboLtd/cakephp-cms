<?php
namespace Cms\Test\TestCase\Controller;

use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;
use Cms\Model\Entity\Article;
use Cms\Model\Entity\Site;

/**
 * @property \Cms\Model\Table\ArticlesTable $Articles
 * @property \Cms\Model\Table\ArticleFeaturedImagesTable $ArticleFeaturedImages
 */
class ArticlesControllerTest extends IntegrationTestCase
{
    public $fixtures = [
        'plugin.cms.articles',
        'plugin.cms.categories',
        'plugin.cms.sites',
        'plugin.cms.file_storage',
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
         * @var \Cms\Model\Table\ArticlesTable $table
         */
        $table = TableRegistry::get('Cms.Articles');
        $this->Articles = $table;

        // Save featured image
        $data = [
            'foreign_key' => '00000000-0000-0000-0000-000000000001',
            'model' => 'ArticleFeaturedImage',
            'filename' => 'cake.icon.png',
        ];
        $table = $this->Articles->ArticleFeaturedImages;
        $entity = $table->newEntity();
        $entity = $table->patchEntity($entity, $data);
        $table->save($entity);
    }

    public function tearDown()
    {
        unset($this->Articles);

        parent::tearDown();
    }

    public function testView(): void
    {
        $this->get('/cms/site/blog/articles/view/article/first-article');
        $this->assertResponseOk();

        $this->assertInstanceOf(Article::class, $this->viewVariable('article'));
        $this->assertInstanceOf(Site::class, $this->viewVariable('site'));
        $this->assertEquals('article', $this->viewVariable('type'));
        $this->assertInternalType('array', $this->viewVariable('article')->get('article_featured_images'));
        $this->assertInternalType('array', $this->viewVariable('site')->get('categories'));
        $this->assertInternalType('array', $this->viewVariable('categories'));
        $this->assertInternalType('array', $this->viewVariable('filteredCategories'));
    }

    public function testType(): void
    {
        $this->get('/cms/site/blog/type/article/view');
        $this->assertResponseOk();

        $this->assertInstanceOf(ResultSet::class, $this->viewVariable('articles'));
        $this->assertInstanceOf(Site::class, $this->viewVariable('site'));
        $this->assertEquals('article', $this->viewVariable('type'));
        $this->assertInternalType('array', $this->viewVariable('site')->get('categories'));
        $this->assertInternalType('array', $this->viewVariable('categories'));
        $this->assertInternalType('array', $this->viewVariable('filteredCategories'));
    }

    public function testAdd(): void
    {
        $data = [
            'title' => 'New Article',
            'content' => 'Some content',
            'excerpt' => 'Some excerpt',
            'publish_date' => '2017-10-06 11:35:00',
            'category_id' => '00000000-0000-0000-0000-000000000001',
            'file' => ['name' => 'test-file.png', 'error' => false],
        ];
        $this->post('/cms/site/blog/articles/add/article', $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');
        $this->assertSession('The article has been saved.', 'Flash.flash.0.message');
    }

    public function testAddValidationError(): void
    {
        $data = [];
        $this->post('/cms/site/blog/articles/add/article', $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');
        $this->assertSession('The article could not be saved. Please, try again.', 'Flash.flash.0.message');
    }

    public function testAddInvalidType(): void
    {
        $this->post('/cms/site/blog/articles/add/foobar');

        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    /**
     * @dataProvider idsProvider
     */
    public function testEdit(string $id): void
    {
        $data = [
            'title' => 'Modified article title',
            'file' => ['name' => 'test-file.png', 'error' => false],
        ];
        $this->put('/cms/site/blog/articles/edit/article/' . $id, $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');
        $this->assertSession('The article has been saved.', 'Flash.flash.0.message');

        $query = $this->Articles->find()
            ->where(['OR' => ['id' => $id, 'slug' => $id]]);

        $query->enableHydration(true);
        $query->contain('ArticleFeaturedImages');

        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $entity = $query->first();

        $this->assertEquals($data['title'], $entity->get('title'));
    }

    /**
     * @dataProvider idsProvider
     */
    public function testEditInvalidType(string $id): void
    {
        $this->put('/cms/site/blog/articles/edit/foobar/' . $id);

        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    /**
     * @dataProvider errorsProvider
     */
    public function testEditUploadError(int $code, string $message): void
    {
        $data = [
            'title' => 'Modified article title',
            'file' => ['name' => 'test-file.png', 'error' => $code],
        ];
        $this->put('/cms/site/blog/articles/edit/article/first-article', $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');

        $this->assertSession($message, 'Flash.flash.0.message');
        if (UPLOAD_ERR_NO_FILE !== $code) {
            $this->assertSession('The article has been saved.', 'Flash.flash.1.message');
        }
    }

    public function testEditNoUploadError(): void
    {
        $data = [
            'title' => 'Modified article title',
            'file' => ['name' => 'test-file.png', 'error' => UPLOAD_ERR_NO_FILE],
        ];
        $this->put('/cms/site/blog/articles/edit/article/first-article', $data);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');

        $this->assertSession('The article has been saved.', 'Flash.flash.0.message');
    }

    /**
     * @dataProvider idsProvider
     */
    public function testDelete(string $id): void
    {
        $this->delete('/cms/site/blog/articles/delete/' . $id);

        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
        $this->assertRedirect('/');

        $query = $this->Articles->find()->where(['id' => $id]);

        $this->assertTrue($query->isEmpty());
    }

    public function testDeleteInvalidId(): void
    {
        $id = '00000000-0000-0000-0000-000000000404';
        $this->delete('/cms/site/blog/articles/delete/' . $id);

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
            ['first-article'],
        ];
    }

    /**
     * @return mixed[]
     */
    public function errorsProvider(): array
    {
        return [
            [1, 'The uploaded file exceeds the upload_max_filesize directive in php.ini'],
            [2, 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'],
            [3, 'The uploaded file was only partially uploaded'],
            [6, 'Missing a temporary folder'],
            [7, 'Failed to write file to disk'],
            [8, 'File upload stopped by extension'],
            [1234, 'Unknown upload error'],
        ];
    }
}
