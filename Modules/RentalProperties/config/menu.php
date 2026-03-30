<?php

return [
    'show_in_menu' => true,
    'section' => 'Rentals',
    'items' => [
        ['label' => 'All Rentals', 'icon' => 'home', 'route' => 'admin.rentals.index'],
        ['label' => 'Add Rental', 'icon' => 'plus-circle', 'route' => 'admin.rentals.create'],
    ],
];
