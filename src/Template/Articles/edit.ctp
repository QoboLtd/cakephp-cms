<?php
use Cake\I18n\Time;

echo $this->Html->css(
    [
        'AdminLTE./plugins/daterangepicker/daterangepicker-bs3',
        'AdminLTE./plugins/select2/select2.min',
        'Cms.select2-bootstrap.min'
    ],
    [
        'block' => 'css'
    ]
);
echo $this->Html->script(
    [
        'AdminLTE./plugins/daterangepicker/moment.min',
        'AdminLTE./plugins/daterangepicker/daterangepicker',
        'AdminLTE./plugins/select2/select2.full.min'
    ],
    ['block' => 'scriptBotton']
);
echo $this->Html->scriptBlock(
    '$(".select2").select2({
        theme: "bootstrap",
        placeholder: "-- Please choose --",
        escapeMarkup: function (text) { return text; }
    });',
    ['block' => 'scriptBotton']
);
echo $this->Html->scriptBlock(
    '$(".datetimepicker").daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        timePicker: true,
        drops: "down",
        timePicker12Hour: false,
        timePickerIncrement: 5,
        format: "YYYY-MM-DD HH:mm"
    });',
    ['block' => 'scriptBotton']
);
$ckeditorId = 'ckeditor' . uniqid();
echo $this->element('Cms.ckeditor', [
    'id' => $ckeditorId,
    'url' => $this->Url->assetUrl(['action' => 'uploadFromEditor', $article->id, '_ext' => 'json'])
]);
?>
<section class="content-header">
    <h1><?= __('Edit {0}', ['Article']) ?></h1>
</section>
<section class="content">
    <?= $this->Form->create($article, ['type' => 'file']) ?>
    <div class="row">
        <div class="col-lg-4 col-lg-push-8">
            <div class="row">
                <div class="col-xs-12 col-md-4 col-lg-12">
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <i class="fa fa-info-circle"></i>
                            <h3 class="box-title">Info</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-xs-12">
                                    <?= $this->Form->input('title') ?>
                                </div>
                                <div class="col-xs-12">
                                    <div><?= $this->Form->label(__('Category')); ?></div>
                                    <?= $this->Form->select('category_id', $categories, [
                                        'class' => 'select2'
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-12">
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <i class="fa fa-calendar"></i>
                            <h3 class="box-title">Publish</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-xs-12">
                                    <?= $this->Form->input('publish_date', [
                                        'type' => 'text',
                                        'class' => 'datetimepicker',
                                        'autocomplete' => 'off',
                                        'value' => $article->publish_date instanceof Time ?
                                            $article->publish_date->i18nFormat('yyyy-MM-dd HH:mm') :
                                            $article->publish_date,
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
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-md-4 col-lg-12">
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <i class="fa fa-file-image-o"></i>
                            <h3 class="box-title">Featured Image</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="form-group">
                                        <label class="control-label" for="featured-image">
                                            <?= __d('cms', 'Featured Image') ?>
                                        </label>
                                        <?= $this->Form->file('file') ?>
                                        <?= $this->Form->error('file') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 col-lg-pull-4">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <i class="fa fa-pencil-square-o"></i>
                    <h3 class="box-title">Content</h3>
                </div>
                <div class="box-body">
                    <?= $this->Form->input('content', ['type' => 'textarea', 'id' => $ckeditorId, 'label' => false]) ?>
                </div>
            </div>
            <div class="box box-solid">
                <div class="box-header with-border">
                    <i class="fa fa-ellipsis-h"></i>
                    <h3 class="box-title">Excerpt</h3>
                </div>
                <div class="box-body">
                    <?= $this->Form->input('excerpt', ['type' => 'textarea', 'label' => false]) ?>
                </div>
            </div>
        </div>
    </div>
    <?= $this->Form->button(__('Submit'), ['class' => 'btn-primary']) ?>
    <?= $this->Form->end() ?>
</section>