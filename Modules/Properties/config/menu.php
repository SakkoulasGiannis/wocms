<?php

return [
    'show_in_menu' => true,
    'section' => 'Properties',
    'items' => [
        ['label' => 'All Properties', 'icon' => 'building', 'route' => 'admin.properties.index'],
        ['label' => 'Add Property', 'icon' => 'plus-circle', 'route' => 'admin.properties.create'],
    ],
];
