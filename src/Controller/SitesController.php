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
 * Sites Controller
 *
 * @property \Cms\Model\Table\SitesTable $Sites
 */
class SitesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void|null
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
     * @param string $id Site id or slug.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     *
     * @return \Cake\Http\Response|void|null
     */
    public function view(string $id)
    {
        $site = $this->Sites->getSite($id, true, true);

        $this->set('site', $site);
        $this->set('categories', $this->Sites->Categories->getTreeList($site->id));
        $this->set('filteredCategories', $this->Sites->Categories->getTreeList($site->id, '', true));
        $this->set('_serialize', ['site']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|void|null
     */
    public function add()
    {
        $this->request->allowMethod(['post']);

        $site = $this->Sites->newEntity();
        $site = $this->Sites->patchEntity($site, (array)$this->request->getData());

        if ($this->Sites->save($site)) {
            $this->Flash->success((string)__('The site has been saved.'));
            $redirectUrl = [
                'plugin' => 'Cms',
                'controller' => 'Sites',
                'action' => 'view',
                $site->get('slug'),
            ];
        } else {
            $this->Flash->error((string)__('The site could not be saved. Please, try again.'));
            $redirectUrl = ['plugin' => 'Cms', 'controller' => 'Sites', 'action' => 'index'];
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * Edit method
     *
     * @param string|null $id Site id.
     *
     * @return \Cake\Http\Response|void|null
     */
    public function edit(?string $id)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        $site = $this->Sites->get($id);
        $site = $this->Sites->patchEntity($site, (array)$this->request->getData());

        if ($this->Sites->save($site)) {
            $this->Flash->success((string)__('The site has been saved.'));
        } else {
            $this->Flash->error((string)__('The site could not be saved. Please, try again.'));
        }

        $redirect = ['plugin' => 'Cms', 'controller' => 'Sites', 'action' => 'index'];
        if ($site->get('active')) {
            $redirect['action'] = 'view';
            $redirect[] = $site->get('slug');
        }

        return $this->redirect($redirect);
    }

    /**
     * Delete method
     *
     * @param string $id Site id.
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     *
     * @return \Cake\Http\Response|void|null Redirects to index.
     */
    public function delete(string $id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $site = $this->Sites->get($id);

        if ($this->Sites->delete($site)) {
            $this->Flash->success((string)__('The site has been deleted.'));
        } else {
            $this->Flash->error((string)__('The site could not be deleted. Please, try again.'));
        }

        return $this->redirect(['plugin' => 'Cms', 'controller' => 'Sites', 'action' => 'index']);
    }
}
