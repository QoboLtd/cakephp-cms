<?php
use Migrations\AbstractMigration;

class RenameArticlesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $this->table('articles')
            ->rename('qobo_cms_articles')
            ->save();
    }
}
