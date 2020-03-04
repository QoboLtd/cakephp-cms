<?php
use Cake\Routing\Router;

return [
    'TinymceElfinder' => [
        'title' => 'File Manager',
        'client_options' => [
            'requestType' => 'post',
            'width' => 900,
            'height' => 500,
            'resizable' => 'yes',
            'soundPath' => '/cakephp_tinymce_elfinder/elfinder/sounds',
        ],
        'static_files' => [
            'js' => [
                'jquery' => 'AdminLTE./bower_components/jquery/dist/jquery.min.js',
                'jquery_ui' => 'Cms./plugins/jquery-ui/jquery-ui.min',
            ],
            'css' => [
                'jquery_ui' => 'Cms./plugins/jquery-ui/jquery-ui.min',
                'jquery_ui_theme' => 'Cms./plugins/jquery-ui/jquery-ui.theme.min',
            ],
        ],
        'options' => [
            // enabling debug modifies output buffering which causes rendering issues (task #4084)
            'debug' => false,
            'roots' => [
                [
                    'id' => 'cms_uploads',
                    'driver' => 'LocalFileSystem', // driver for accessing file system (REQUIRED]
                    'URL' => Router::fullBaseUrl() . '/uploads/cms', // upload main folder
                    'path' => WWW_ROOT . 'uploads/cms', // path to files (REQUIRED]
                    'attributes' => [
                        [
                            'pattern' => '!thumbnails!',
                            'hidden' => true,
                        ],
                    ],
                    'tmbPathMode' => 0755,
                    'tmbPath' => 'thumbnails',
                    'tmbSize' => 150,
                    'uploadOverwrite' => true,
                    'uploadMaxSize' => (string)ini_get('upload_max_filesize'),
                    'checkSubfolders' => false,
                    'disabled' => [],
                ],
            ],
        ],
    ],
];
