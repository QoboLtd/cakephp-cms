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

use Cake\Cache\Cache;
use Cake\I18n\Time;
use Cake\Utility\Inflector;
use Cms\View\Shortcode;

// load lightbox library
$this->Html->css('Cms./plugins/lightbox2/css/lightbox.min', ['block' => 'css']);
$this->Html->script('Cms./plugins/lightbox2/js/lightbox.min', ['block' => 'scriptBottom']);

$element = 'Plugin/Cms/' . Inflector::camelize($article->type) . '/single';

// fallback to plugin's element
if (!$this->elementExists($element)) {
    $element = 'Types/' . Inflector::camelize($article->type) . '/single';
}

// fallback to default element
if (!$this->elementExists($element)) {
    $element = 'Types/Common/single';
}

// render shortcodes
if (!empty($types[$article->type]['fields'])) {
    foreach ($types[$article->type]['fields'] as $info) {
        // skip empty values
        if (!$article->get($info['field'])) {
            continue;
        }

        // skip non-editor fields
        if (!$info['editor']) {
            continue;
        }

        $shortcodes = Shortcode::get($article->get($info['field']));
        if (empty($shortcodes)) {
            continue;
        }

        $content = $article->get($info['field']);
        foreach ($shortcodes as $shortcode) {
            $cacheKey = 'shortcode_' . md5(json_encode($shortcode));
            $parsed = Cache::read($cacheKey);
            if (!$parsed) {
                $parsed = Shortcode::parse($shortcode);
                Cache::write($cacheKey, $parsed);
            }

            $content = str_replace($shortcode['full'], $parsed, $content);
        }

        $article->set($info['field'], $content);
    }
}

$data = ['article' => $article];

$isPublished = $article->publish_date <= Time::now();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box box-<?= $isPublished ? 'primary' : 'danger' ?> single-item">
            <div class="box-header with-border">
                <i class="fa fa-<?= $articleTypes[$article->type]['icon'] ?>"></i>
                <h3 class="box-title"><?= $article->title ?></h3>
                <div class="box-tools pull-right">
                    <?= $this->element('Cms./Menu/article-actions', ['site' => $site, 'article' => $article]) ?>
                </div>
            </div>
            <div class="box-body">
                <?= $this->element($element, $data); ?>
            </div>
            <div class="box-footer small">
                <div class="text-right text-muted">
                <?= $this->Html->link($article->category->name, [
                    'controller' => 'Categories',
                    'action' => 'view',
                    $site->slug,
                    $article->category->slug
                ]) ?>
                |
                <?php if ($isPublished) : ?>
                    <?= __('Published') ?>
                    <?= $article->publish_date->timeAgoInWords([
                        'format' => 'MMM d, YYY | HH:mm',
                        'end' => '1 month'
                    ]) ?>
                <?php else : ?>
                    <?= __('Unpublished') ?> <i class="fa fa-eye-slash text-danger"></i>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
