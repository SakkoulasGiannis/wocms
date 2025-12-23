<?php

namespace App\Services;

class IconLibrary
{
    /**
     * Get all available Hero Icons organized by category
     */
    public static function getHeroIcons(): array
    {
        return [
            'Common' => [
                'home' => 'Home',
                'document-text' => 'Document',
                'newspaper' => 'Newspaper',
                'photograph' => 'Photo',
                'users' => 'Users',
                'folder' => 'Folder',
            ],
            'Business' => [
                'briefcase' => 'Briefcase',
                'chart-bar' => 'Chart',
                'presentation-chart-line' => 'Presentation',
                'library' => 'Library',
                'cube' => 'Cube',
            ],
            'E-commerce' => [
                'shopping-cart' => 'Shopping Cart',
                'shopping-bag' => 'Shopping Bag',
                'tag' => 'Tag',
                'gift' => 'Gift',
            ],
            'Communication' => [
                'chat' => 'Chat',
                'mail' => 'Mail',
                'phone' => 'Phone',
                'bell' => 'Bell',
            ],
            'Navigation' => [
                'cog' => 'Settings',
                'calendar' => 'Calendar',
                'clock' => 'Clock',
                'globe' => 'Globe',
            ],
            'Other' => [
                'star' => 'Star',
                'heart' => 'Heart',
                'fire' => 'Fire',
                'light-bulb' => 'Light Bulb',
                'sparkles' => 'Sparkles',
            ],
        ];
    }

    /**
     * Get grouped options for select dropdown
     */
    public static function getGroupedOptions(): array
    {
        $icons = self::getHeroIcons();
        $options = [];

        foreach ($icons as $category => $items) {
            $options[$category] = [];
            foreach ($items as $iconName => $label) {
                $options[$category][$iconName] = $label;
            }
        }

        return $options;
    }

    /**
     * Get all icon names as a flat array
     */
    public static function getAllIconNames(): array
    {
        $allNames = [];
        foreach (self::getHeroIcons() as $category => $items) {
            $allNames = array_merge($allNames, array_keys($items));
        }
        return $allNames;
    }
}
