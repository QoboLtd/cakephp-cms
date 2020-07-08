<?php
use Migrations\AbstractMigration;

class UpdateArticlesFields extends AbstractMigration
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
        $table = $this->table('articles');
        $table
            //Categories has been moved to a table.
            ->removeColumn('category')
            //Should be datetime for sorting the article by date.
            ->removeColumn('publish_date')
            ->save();

        $table
            ->addColumn('publish_date', 'datetime', ['after' => 'modified_by'])
            ->save();
    }
}
