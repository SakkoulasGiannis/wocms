<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;

class FrontendMenuService
{
    public function get(string $locationOrSlug): ?Menu
    {
        return Cache::remember("frontend_menu.{$locationOrSlug}", 3600, function () use ($locationOrSlug) {
            return Menu::where('is_active', true)
                ->where(fn ($q) => $q->where('location', $locationOrSlug)->orWhere('slug', $locationOrSlug))
                ->with(['rootItems' => fn ($q) => $q->where('is_active', true)->orderBy('order')
                    ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('order')
                        ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('order')])])])
                ->first();
        });
    }

    public function clearCache(): void
    {
        Cache::forget('frontend_menus');
        $menus = Menu::all();
        foreach ($menus as $menu) {
            Cache::forget("frontend_menu.{$menu->slug}");
            if ($menu->location) {
                Cache::forget("frontend_menu.{$menu->location}");
            }
        }
    }
}
