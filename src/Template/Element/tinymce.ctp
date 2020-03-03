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

// get elFinder script
$elFinderHtml = $this->TinymceElfinder->defineElfinderBrowser(true);

// strip out < script > tags
$dom = new DOMDocument();
$dom->loadHtml($elFinderHtml);
$script = $dom->getElementsByTagName('script');
$elFinderHtml = trim($script->item(0)->nodeValue);

// load tinyMCE editor and elFinder file manager
echo $this->Html->script('Cms./plugins/tinymce/tinymce.min', ['block' => 'scriptBottom']);
echo $this->Html->scriptBlock($elFinderHtml, ['block' => 'scriptBottom']);

// initialize tinyMCE
echo $this->Html->scriptBlock(
    'var tinymce_init_config = ' . json_encode(Configure::read('TinyMCE')) . ';',
    ['block' => 'scriptBottom']
);
echo $this->Html->script('Cms.tinymce.init', ['block' => 'scriptBottom']);
