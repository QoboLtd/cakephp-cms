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
use Cake\Core\Configure;

echo $this->Html->css('Cms./plugins/datatables/css/dataTables.bootstrap.min', ['block' => 'css']);
echo $this->Html->script(
    [
        'Cms./plugins/datatables/datatables.min',
        'Cms./plugins/datatables/js/dataTables.bootstrap.min',
    ],
    ['block' => 'scriptBottom']
);
echo $this->Html->scriptBlock(
    ';(function ($) {
        $(".table-datatable").DataTable({
            stateSave: true,
            stateDuration: ' . (int)(Configure::read('Session.timeout') * 60) . '
        });
    })(jQuery);',
    ['block' => 'scriptBottom']
);
?>
<section class="content-header">
    <h1>Sites
        <div class="pull-right">
            <div class="btn-group btn-group-sm" role="group">
                <?= $this->Html->link('<i class="fa fa-plus"></i> ' . __('Add'), '#', [
                    'title' => __('Add'),
                    'class' => 'btn btn-default',
                    'data-toggle' => 'modal',
                    'data-target' => '#cms-site-add',
                    'escape' => false
                ]) ?>
            </div>
        </div>
    </h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            <table class="table table-hover table-condensed table-vertical-align table-datatable" width="100%">
                <thead>
                    <tr>
                        <th><?= __('Name'); ?></th>
                        <th><?= __('Slug'); ?></th>
                        <th><?= __('Active'); ?></th>
                        <th class="actions"><?= __('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sites as $site) : ?>
                    <tr>
                        <td><?= $site->name ?></td>
                        <td><?= $site->slug ?></td>
                        <td><?= $site->active ? __('Yes') : __('No') ?></td>
                        <td class="actions">
                            <div class="btn-toolbar" role="toolbar">
                                <div class="btn-group btn-group-xs" role="group">
                                    <?= $this->Html->link(
                                        '<i class="fa fa-eye"></i>',
                                        ['action' => 'view', $site->slug],
                                        ['title' => __('View'), 'class' => 'btn btn-default', 'escape' => false]
                                    ) ?>
                                    <?php if (!$site->active) : ?>
                                        <?= $this->Html->link('<i class="fa fa-pencil"></i>', '#', [
                                            'title' => __('Edit'),
                                            'class' => 'btn btn-default',
                                            'data-toggle' => 'modal',
                                            'data-target' => '#cms-site-edit' . $site->id,
                                            'escape' => false
                                        ]) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?= $this->element('Cms.Sites/modal', ['site' => null]) ?>
<?php
foreach ($sites as $site) {
    if ($site->active) {
        continue;
    }

    echo $this->element('Cms.Sites/modal', ['site' => $site]);
}
