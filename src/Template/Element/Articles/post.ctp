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

use Cake\I18n\Time;

echo $this->Html->css(
    [
        'AdminLTE./plugins/daterangepicker/daterangepicker',
        'AdminLTE./plugins/select2/select2.min',
        'Qobo/Cms.style',
        'Qobo/Utils.select2-bootstrap.min',
        'Qobo/Utils.select2-style'
    ],
    [
        'block' => 'css'
    ]
);
echo $this->Html->script(
    [
        'AdminLTE./plugins/daterangepicker/moment.min',
        'AdminLTE./plugins/daterangepicker/daterangepicker',
        'AdminLTE./plugins/select2/select2.full.min',
        'Qobo/Cms.select2.init',
        'Qobo/Cms.datetimepicker.init',
    ],
    ['block' => 'scriptBottom']
);

$formOptions = ['type' => 'file'];
if (!empty($url)) {
    $formOptions['url'] = $url;
}

$publishDate = $article && $article->publish_date ?
    $article->publish_date->i18nFormat('yyyy-MM-dd HH:mm') :
    Time::now()->i18nFormat('yyyy-MM-dd HH:mm');
?>
<?= $this->Form->create($article, $formOptions) ?>
<div class="row">
    <div class="col-lg-4 col-lg-push-8">
        <?= $this->Form->input('category_id', [
            'type' => 'select',
            'options' => $categories,
            'value' => isset($category) ? $category->get('id') : null,
            'class' => 'select2',
            'required' => true
        ]); ?>
        <?= $this->Form->input('publish_date', [
            'type' => 'text',
            'required' => true,
            'class' => 'form-control datetimepicker',
            'autocomplete' => 'off',
            'value' => $publishDate,
            'templates' => [
                'input' => '<div class="input-group">
                    <div class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <input type="{{type}}" name="{{name}}"{{attrs}}/>
                </div>'
            ]
        ]) ?>
    </div>
    <div class="col-lg-8 col-lg-pull-4">
        <?= $this->element('Qobo/Cms.Articles/field', [
            'typeOptions' => $typeOptions,
            'article' => $article
        ]) ?>
    </div>
</div>
<?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']) ?>
<?= $this->Form->end() ?>
