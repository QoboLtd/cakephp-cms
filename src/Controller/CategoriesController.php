<?php
namespace Cms\Controller;

use Cms\Controller\AppController;
/**
 * Categories Controller for handling the categories
 * @todo
 * 1. Missing backend Actions
 * 2. Missing Model
 */
class CategoriesController extends AppController
{
    /**
     * Default method
     *
     * @todo Get the category data from the CategoriesModel.
     * @param  string $category Category's slug
     * @return void
     */
    public function display($category = null)
    {
        if (is_null($category)) {
            $this->redirect('/');
        }
        $this->loadModel('Cms.Articles');
        $categories = $this->Articles->getCategories();
        if (!in_array($category, array_keys($categories))) {
            $this->redirect('/');
        }
        $articles = $this->Articles
            ->find('all')
            ->where(['category' => $category])
            ->order(['created' => 'DESC'])
            ->contain(['ArticleFeaturedImages' => ['sort' => ['created' => 'DESC']]]);
        if ($articles->isEmpty()) {
            $this->redirect('/');
        }
        $categoryTitle = $categories[$category];
        $latest = $articles->first();
        $this->set(compact('articles', 'latest', 'categoryTitle'));
    }
}
