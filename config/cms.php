<?php
// CMS plugin configuration
return [
    'CMS' => [
        'Sidebar' => [
            'display' => [
                'categories' => true,
                'types' => true,
            ],
        ],
        'Articles' => [
            'types' => [
                'article' => [
                    'enabled' => true,
                    'label' => 'Article',
                    'icon' => 'pencil-square',
                    'fields' => [
                        'Title' => [
                            'field' => 'title',
                        ],
                        'Featured Image' => [
                            'field' => 'featured_image',
                            'renderAs' => 'file',
                        ],
                        'Excerpt' => [
                            'field' => 'excerpt',
                        ],
                        'Content' => [
                            'field' => 'content',
                            'renderAs' => 'textarea',
                            'editor' => true,
                        ],
                    ],
                ],
                'document' => [
                    'enabled' => true,
                    'label' => 'Document',
                    'icon' => 'file-text',
                    'fields' => [
                        'Name' => [
                            'field' => 'title',
                        ],
                        'Description' => [
                            'field' => 'content',
                            'renderAs' => 'textarea',
                            'editor' => true,
                        ],
                    ],
                ],
                'gallery' => [
                    'enabled' => true,
                    'label' => 'Gallery',
                    'icon' => 'picture-o',
                    'fields' => [
                        'Title' => [
                            'field' => 'title',
                        ],
                        'Featured Image' => [
                            'field' => 'featured_image',
                            'renderAs' => 'file',
                        ],
                        'Description' => [
                            'field' => 'excerpt',
                            'renderAs' => 'textarea',
                        ],
                        'Images' => [
                            'field' => 'content',
                            'renderAs' => 'textarea',
                            'editor' => true,
                        ],
                    ],
                ],
                'link' => [
                    'label' => 'Link',
                    'enabled' => true,
                    'icon' => 'link',
                    'fields' => [
                        'Title' => [
                            'field' => 'title',
                        ],
                        'URL' => [
                            'field' => 'content',
                            'renderAs' => 'url',
                        ],
                    ],
                ],
                'faq' => [
                    'enabled' => true,
                    'label' => 'FAQ',
                    'icon' => 'question-circle',
                    'fields' => [
                        'Question' => [
                            'field' => 'title',
                        ],
                        'Answer' => [
                            'field' => 'content',
                            'renderAs' => 'textarea',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
