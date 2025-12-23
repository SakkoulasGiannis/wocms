<?php

/**
 * Example Blog Module - Menu Configuration
 *
 * This file defines how the module appears in the admin navigation.
 * To enable: php artisan module:enable ExampleBlog
 * To disable: php artisan module:disable ExampleBlog
 */

return [
    /**
     * Show this module in the admin menu
     * Set to false to hide from menu even if module is enabled
     */
    'show_in_menu' => true,

    /**
     * Section name - groups menu items together
     */
    'section' => 'Blog Management',

    /**
     * Menu items for this module
     * Each item will appear in the admin navigation
     */
    'items' => [
        [
            'label' => 'All Posts',
            'icon' => 'newspaper',
            'route' => 'admin.blog.posts.index'
        ],
        [
            'label' => 'Categories',
            'icon' => 'folder',
            'route' => 'admin.blog.categories.index'
        ],
        [
            'label' => 'Tags',
            'icon' => 'tags',
            'route' => 'admin.blog.tags.index'
        ]
    ]
];
