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

use Cms\Controller\AppController;

/**
 * Sites Controller
 *
 * @property \Cms\Model\Table\SitesTable $Sites
 */
class SitesController extends AppController
{
    public $paginate = [
        'limit' => 10,
        'order' => [
            'Articles.created' => 'desc'
        ]
    ];

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $sites = $this->Sites->find('all')->all();

        $this->set(compact('sites'));
        $this->set('_serialize', ['sites']);
    }

    /**
     * View method
     *
     * @param string|null $id Site id or slug.
     * @return void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $site = $this->Sites->getSite($id, true);
        $articles = $this->paginate(
            $this->Sites->Articles
                ->find('all')
                ->contain(['ArticleFeaturedImages'])
                ->where(['site_id' => $site->id])
        );

        $this->set('site', $site);
        $this->set('articles', $articles);
        $this->set('categories', $this->Sites->Categories->getTreeList($site->id));
        $this->set('filteredCategories', $this->Sites->Categories->getTreeList($site->id, '', true));
        $this->set('_serialize', ['site']);
    }

    /**
     * Add method
     *
     * @return \Cake\Network\Response
     */
    public function add()
    {
        $this->request->allowMethod(['post']);

        $site = $this->Sites->newEntity();
        $site = $this->Sites->patchEntity($site, $this->request->data);
        if ($this->Sites->save($site)) {
            $this->Flash->success(__('The site has been saved.'));
            $redirectUrl = ['plugin' => 'Cms', 'controller' => 'Sites', 'action' => 'view', $site->slug];
        } else {
            $this->Flash->error(__('The site could not be saved. Please, try again.'));
            $redirectUrl = ['plugin' => 'Cms', 'controller' => 'Sites', 'action' => 'index'];
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * Edit method
     *
     * @param string|null $id Site id.
     * @return \Cake\Network\Response
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        $site = $this->Sites->get($id);
        $site = $this->Sites->patchEntity($site, $this->request->data);
        if ($this->Sites->save($site)) {
            $this->Flash->success(__('The site has been saved.'));
        } else {
            $this->Flash->error(__('The site could not be saved. Please, try again.'));
        }

        $redirect = ['plugin' => 'Cms', 'controller' => 'Sites', 'action' => 'index'];
        if ($site->active) {
            $redirect['action'] = 'view';
            $redirect[] = $site->slug;
        }

        return $this->redirect($redirect);
    }

    /**
     * Delete method
     *
     * @param string|null $id Site id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $site = $this->Sites->get($id);
        if ($this->Sites->delete($site)) {
            $this->Flash->success(__('The site has been deleted.'));
        } else {
            $this->Flash->error(__('The site could not be deleted. Please, try again.'));
        }

        return $this->redirect(['plugin' => 'Cms', 'controller' => 'Sites', 'action' => 'index']);
    }
}
