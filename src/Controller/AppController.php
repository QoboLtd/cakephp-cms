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

use App\Controller\AppController as BaseController;
use Cake\Event\Event;
use Cake\Utility\Hash;
use Cms\Model\Table\ArticlesTable;

class AppController extends BaseController
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
    }

    /**
     * {@inheritDoc}
     */
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $table = $this->{$this->name};
        $table = $table->hasAssociation('Articles') ? $this->{$this->name}->Articles->getTarget() : $table;

        if (!$table instanceof ArticlesTable) {
            return null;
        }

        // pass article types to all Views
        $this->set('types', $table->getTypes());

        $searchQuery = Hash::get($this->request->getQueryParams(), 'q', '');

        $searchTitle = $searchQuery ? sprintf("%s '%s'", (string)__('Search Results for'), $searchQuery) : '';

        // pass search title to all Views
        $this->set('searchTitle', $searchTitle);

        // set search query on Articles table
        $table->setSearchQuery($searchQuery);
    }

    /**
     * {@inheritDoc}
     */
    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);

        $this->viewBuilder()->setHelpers(['CakephpTinymceElfinder.TinymceElfinder']);
        $this->set('user', $this->Auth->user());
    }
}
