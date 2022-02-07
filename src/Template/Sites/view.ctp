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
?>
<section class="content-header">
    <h1><?= h($site->name) ?> <small><?= $searchTitle ?></small></h1>
    <div class="btn-group btn-group-sm toolbox pull-right" role="group">
        <?= $this->element('Cms.Sites/toolbar', ['site' => $site, 'user' => $user]) ?>
    </div>
</section>
<section class="content">
    <?= $this->element('Cms.Sites/manage', [
        'articles' => $articles,
        'categories' => $categories,
        'site' => $site,
        'article' => null,
        'types' => $types
    ]) ?>
    <div class="row">
        <div class="col-xs-12 col-md-3 col-md-push-9">
            <?= $this->element('Cms.sidebar') ?>
        </div>
        <div class="col-xs-12 col-md-7 col-md-offset-1 col-md-pull-3">
            <?= $this->element('Cms.Articles/list', [
                'site' => $site,
                'articles' => $articles,
                'articleTypes' => $types
            ]) ?>
         <div class="paginator">
             <?= $this->Paginator->counter([
                 'format' => __('Showing {{start}} to {{end}} of {{count}} entries')
             ]) ?>
             <ul class="pagination pagination-sm no-margin pull-right">
                 <?= $this->Paginator->prev('&laquo;', ['escape' => false]) ?>
                 <?= $this->Paginator->numbers() ?>
                 <?= $this->Paginator->next('&raquo;', ['escape' => false]) ?>
             </ul>
         </div>
        </div>
    </div>
</section>
