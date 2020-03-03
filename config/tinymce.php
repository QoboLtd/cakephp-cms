<?php
// TinyMCE plugin configuration
return [
    'TinyMCE' => [
        'selector' => 'textarea.tinymce',
        'relative_urls' => false,
        'plugins' => ['image', 'link', 'fullscreen', 'textcolor', 'emoticons', 'table', 'code', 'media'],
        'toolbar' => 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image gallery media | code fullscreen | table forecolor backcolor emoticons',
        'browser_spellcheck' => true,
        'file_browser_callback_types' => 'file image',
        'theme' => 'modern',
        'height' => 300,
    ],
];
