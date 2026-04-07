<?php

return [
    'show_in_menu' => true,
    'section' => 'Image Maps',
    'items' => [
        ['label' => 'All Image Maps', 'icon' => 'image', 'route' => 'admin.imagemaps.index'],
        ['label' => 'Create Image Map', 'icon' => 'plus-circle', 'route' => 'admin.imagemaps.create'],
    ],
];
