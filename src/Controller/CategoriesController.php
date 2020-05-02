<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cms\Controller;


/**
 * Categories Controller
 *
 * @property \Cms\Model\Table\CategoriesTable $Categories
 * @property \Cms\Model\Table\ArticleFeaturedImagesTable $ArticleFeaturedImages
 */
class CategoriesController extends AppController
{
    /**
     * View method
     *
     * @param string $siteId Site id or slug.
     * @param string|null $id Category id.
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     *
     * @return void
     */
    public function view(string $siteId, ?string $id): void
    {
        $site = $this->Categories->Sites->getSite($siteId, true);
        $category = $this->Categories->getBySite($id, $site);
        $categories = $this->Categories->getTreeList($site->id);

        $categoryIds = [];
        $query = $this->Categories->find('list')->where(['Categories.parent_id' => $category->id]);
        if (!$query->isEmpty()) {
            $categoryIds = array_keys($query->toArray());
        }
        $categoryIds[] = $category->id;

        $articles = $this->Categories->Articles->getArticlesByCategory($categoryIds);
        $category->set('articles', $articles->toArray());

        $this->set('filteredCategories', $this->Categories->getTreeList($site->id, '', true));
        $this->set(compact('site', 'category', 'categories'));
        $this->set('_serialize', ['category']);
    }

    /**
     * Add method
     *
     * @param string $siteId Site id or slug.
     *
     * @return \Cake\Http\Response|void|null Redirects on successful add, renders add otherwise.
     */
    public function add(string $siteId)
    {
        $this->request->allowMethod(['post']);

        $site = $this->Categories->Sites->getSite($siteId);
        $category = $this->Categories->newEntity();

        $data = ['site_id' => $site->id];
        $data = array_merge((array)$this->request->getData(), $data);

        $category = $this->Categories->patchEntity($category, $data);
        if ($this->Categories->save($category)) {
            $this->Flash->success((string)__('The category has been saved.'));
        } else {
            $this->Flash->error((string)__('The category could not be saved. Please, try again.'));
        }

        return $this->redirect($this->referer());
    }

    /**
     * Edit method
     *
     * @param string $siteId Site id or slug.
     * @param string|null $id Category id or slug.
     *
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     *
     * @return \Cake\Http\Response|void|null
     */
    public function edit(string $siteId, ?string $id)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        $site = $this->Categories->Sites->getSite($siteId);
        $category = $this->Categories->getBySite($id, $site);

        $data = ['site_id' => $site->id];
        $data = array_merge((array)$this->request->getData(), $data);

        $category = $this->Categories->patchEntity($category, $data);

        if ($this->Categories->save($category)) {
            $this->Flash->success((string)__('The category has been saved.'));
        } else {
            $this->Flash->error((string)__('The category could not be saved. Please, try again.'));
        }

         return $this->redirect($this->referer());
    }

    /**
     * Delete method
     *
     * @param string $siteId Site id or slug.
     * @param string|null $id Category id.
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     *
     * @return \Cake\Http\Response|void|null
     */
    public function delete(string $siteId, ?string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $site = $this->Categories->Sites->getSite($siteId);
        $category = $this->Categories->getBySite($id, $site);

        if ($this->Categories->delete($category)) {
            $this->Flash->success((string)__('The category has been deleted.'));
        } else {
            $this->Flash->error((string)__('The category could not be deleted. Please, try again.'));
        }

        $redirect = $this->referer();
        if (false !== strpos($redirect, $category->get('slug'))) {
            $redirect = ['controller' => 'Sites', 'action' => 'view', $site->get('slug')];
        }

        return $this->redirect($redirect);
    }

    /**
     * Move the node.
     *
     * @param string $siteId Site id or slug
     * @param string|null $id category id
     * @param string $action move action
     *
     * @throws \Cake\Datasource\Exception\InvalidPrimaryKeyException
     *
     * @return \Cake\Http\Response|void|null
     */
    public function moveNode(string $siteId, ?string $id, string $action = '')
    {
        $moveActions = ['up', 'down'];

        if (!in_array($action, $moveActions)) {
            $this->Flash->error((string)__('Unknown move action.'));

            return $this->redirect($this->referer());
        }

        $site = $this->Categories->Sites->getSite($siteId);
        $category = $this->Categories->getBySite($id, $site);

        $moveFunction = 'move' . $action;

        // persist tree structure per site
        $treeBehavior = $this->Categories->getBehavior('Tree');
        $treeBehavior->setConfig('scope', ['site_id' => $category->get('site_id')]);

        if ($this->Categories->{$moveFunction}($category)) {
            $this->Flash->success((string)__('{0} has been moved {1} successfully.', $category->get('name'), $action));
        } else {
            $this->Flash->error((string)__('Fail to move {0} {1}.', $category->get('name'), $action));
        }

        return $this->redirect($this->referer());
    }
}
