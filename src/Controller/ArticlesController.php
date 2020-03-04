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

use Cake\Http\Exception\NotFoundException;
use Cms\Controller\AppController;
use Cms\Controller\UploadTrait;

/**
 * Articles Controller
 *
 * @property \Cms\Model\Table\ArticlesTable $Articles
 * @property \Cms\Model\Table\SitesTable $Sites
 */
class ArticlesController extends AppController
{
    use UploadTrait;

    /**
     * View method
     *
     * @param string $siteId Site id or slug.
     * @param string $typeId Type slug.
     * @param string $id Article id.
     *
     * @return \Cake\Http\Response|void|null
     */
    public function view(string $siteId, string $typeId, string $id)
    {
        $site = $this->Articles->Sites->getSite($siteId, true);

        $this->set('site', $site);
        $this->set('type', $typeId);
        $this->set('article', $this->Articles->getArticle($id, $site->get('id'), true));
        $this->set('categories', $this->Articles->Categories->getTreeList($site->id));
        $this->set('filteredCategories', $this->Articles->Categories->getTreeList($site->id, '', true));
        $this->set('_serialize', ['article']);
    }

    /**
     * Type method
     *
     * @param string $siteId Site id or slug.
     * @param string $typeId Type slug.
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     *
     * @return \Cake\Http\Response|void|null
     */
    public function type(string $siteId, string $typeId)
    {
        $site = $this->Articles->Sites->getSite($siteId, true);

        $articles = $this->Articles->getArticles($site->id, $typeId, true);

        $this->set('type', $typeId);
        $this->set('site', $site);
        $this->set('articles', $articles);
        $this->set('categories', $this->Articles->Categories->getTreeList($site->id));
        $this->set('filteredCategories', $this->Articles->Categories->getTreeList($site->id, '', true));
        $this->set('_serialize', ['type']);
    }

    /**
     * Add method
     *
     * @param string $siteId Site id or slug
     * @param string $type Site type
     *
     * @throws \Cake\Http\Exception\NotFoundException
     *
     * @return \Cake\Http\Response|void|null
     */
    public function add(string $siteId, string $type)
    {
        $this->request->allowMethod(['post']);

        $typeOptions = $this->Articles->getTypeOptions($type);

        if (empty($typeOptions)) {
            throw new NotFoundException('Unsupported Article type provided.');
        }

        $site = $this->Articles->Sites->getSite($siteId);

        $data = [
            'site_id' => $site->id,
            'type' => $type,
            'created_by' => $this->Auth->user('id'),
            'modified_by' => $this->Auth->user('id'),
        ];
        $requestData = (array)$this->request->getData();

        $data = array_merge($requestData, $data);

        $article = $this->Articles->newEntity();
        $article = $this->Articles->patchEntity($article, $data);

        if ($this->Articles->save($article)) {
            $this->Flash->success((string)__('The article has been saved.'));
            //Upload the featured image when there is one.
            if ($this->_isValidUpload($requestData)) {
                $this->_upload($article->get('id'));
            }
        } else {
            $this->Flash->error((string)__('The article could not be saved. Please, try again.'));
        }

        return $this->redirect($this->referer());
    }

    /**
     * Edit method
     *
     * @param string $siteId Site id or slug.
     * @param string $type Site type.
     * @param string $id Article id.
     *
     * @throws \Cake\Http\Exception\NotFoundException
     *
     * @return \Cake\Http\Response|void|null
     */
    public function edit(string $siteId, string $type, string $id)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        $typeOptions = $this->Articles->getTypeOptions($type);

        if (empty($typeOptions)) {
            throw new NotFoundException('Unsupported Article type provided.');
        }

        $site = $this->Articles->Sites->getSite($siteId);

        $data = [
            'site_id' => $site->id,
            'type' => $type,
            'modified_by' => $this->Auth->user('id'),
        ];
        $requestData = (array)$this->request->getData();
        $data = array_merge($requestData, $data);

        $article = $this->Articles->getArticle($id, $site->get('id'));
        $article = $this->Articles->patchEntity($article, $data);

        if ($this->Articles->save($article)) {
            //Upload the featured image when there is one.
            if ($this->_isValidUpload($requestData)) {
                $this->_upload($article->get('id'));
            }
            $this->Flash->success((string)__('The article has been saved.'));
        } else {
            $this->Flash->error((string)__('The article could not be saved. Please, try again.'));
        }

        return $this->redirect($this->referer());
    }

    /**
     * Delete method
     *
     * @param string $siteId Site id or slug.
     * @param string $id Article id.
     *
     * @return \Cake\Http\Response|void|null
     */
    public function delete(string $siteId, string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $site = $this->Articles->Sites->getSite($siteId);
        $article = $this->Articles->getArticle($id, $site->get('id'));

        if ($this->Articles->delete($article)) {
            $this->Flash->success((string)__('The article has been deleted.'));
        } else {
            $this->Flash->error((string)__('The article could not be deleted. Please, try again.'));
        }

        $redirect = $this->referer();
        if (false !== strpos($redirect, $article->get('slug'))) {
            $redirect = ['controller' => 'Sites', 'action' => 'view', $site->get('slug')];
        }

        return $this->redirect($redirect);
    }

    /**
     * Uploads and stores the related file.
     *
     * @param string $articleId id of the relate slide
     *
     * @return void
     */
    protected function _upload(string $articleId): void
    {
        $entity = $this->Articles->ArticleFeaturedImages->newEntity();
        $entity = $this->Articles->ArticleFeaturedImages->patchEntity(
            $entity,
            (array)$this->request->getData()
        );

        // upload image
        $uploaded = $this->Articles->ArticleFeaturedImages->uploadImage($articleId, $entity);
        if ($uploaded) {
            // delete old image
            $this->Articles->ArticleFeaturedImages->deleteAll([
                'ArticleFeaturedImages.foreign_key' => $articleId,
                'ArticleFeaturedImages.path !=' => $entity->get('path'),
            ]);
        }
    }
}
