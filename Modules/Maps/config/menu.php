<?php

return [
    'show_in_menu' => true,
    'section' => 'Maps',
    'items' => [
        ['label' => 'All Maps', 'icon' => 'map', 'route' => 'admin.maps.index'],
        ['label' => 'Create Map', 'icon' => 'plus-circle', 'route' => 'admin.maps.create'],
    ],
];
