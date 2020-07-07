<?php

use Cake\Utility\Text;
use Faker\Factory;
use Phinx\Seed\AbstractSeed;

/**
 * Articles seed.
 */
class ArticlesSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     *
     * @return void
     */
    public function run()
    {
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $uuid = Faker\Provider\Uuid::uuid();
            $title = Faker\Provider\Lorem::sentence(rand(3, 5), true);
            $slug = Text::slug(strtolower($title));
            $excerpt = Faker\Provider\Lorem::text(150);
            $content = Faker\Provider\Lorem::paragraphs(rand(3, 5), true);
            $data[] = [
              'id' => $uuid,
              'title' => $title,
              'slug' => $slug,
              'excerpt' => $excerpt,
              'content' => $content,
              'created_by' => 'qobo',
              'modified_by' => 'qobo',
              'publish_date' => date('Y-m-d H:i:s'),
              'created' => date('Y-m-d H:i:s'),
              'modified' => date('Y-m-d H:i:s')
            ];
        }
        $articles = $this->table('articles');
        $articles
          ->insert($data)
          ->save($data);
    }
}
