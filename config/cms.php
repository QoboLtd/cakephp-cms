<?php
use Cake\Core\Configure;

// CMS plugin configuration
return [
    'CMS' => [
        'Articles' => [
            'structure' => [
                'content' => [
                    'content'
                ],
                'excerpt' => [
                    'excerpt'
                ],
                'info' => [
                    'title'
                ],
                'publish' => [
                    'publish_date'
                ],
                'featured_image' => [
                    'featured_image'
                ]
            ],
            'types' => [
                'article' => [
                    'enabled' => true,
                    'icon' => 'file-text',
                    'fields' => [
                        'Title' => [
                            'field' => 'title'
                        ],
                        'Featured Image' => [
                            'field' => 'featured_image',
                            'renderAs' => 'file'
                        ],
                        'Excerpt' => [
                            'field' => 'excerpt'
                        ],
                        'Content' => [
                            'field' => 'content',
                            'renderAs' => 'textarea',
                            'editor' => true
                        ],
                    ]
                ],
                'gallery' => [
                    'enabled' => true,
                    'icon' => 'picture-o',
                    'fields' => [
                        'Title' => [
                            'field' => 'title',
                        ],
                        'Images' => [
                            'field' => 'content',
                            'renderAs' => 'textarea',
                            'editor' => true
                        ],
                        'Description' => [
                            'field' => 'excerpt',
                            'renderAs' => 'textarea'
                        ],
                        'Featured Image' => [
                            'field' => 'featured_image',
                            'renderAs' => 'file'
                        ]
                    ],
                ],
                'link' => [
                    'enabled' => true,
                    'icon' => 'link',
                    'fields' => [
                        'Title' => [
                            'field' => 'title'
                        ],
                        'URL' => [
                            'field' => 'content',
                            'renderAs' => 'url'
                        ]
                    ]
                ],
            ]
        ]
    ]
];