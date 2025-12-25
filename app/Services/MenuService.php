<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Nwidart\Modules\Facades\Module;

class MenuService
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * Get all menu items including static menu and active module menus (with caching)
     */
    public function getAdminMenu(): array
    {
        return Cache::remember('admin.menu.full', self::CACHE_TTL, function () {
            // Load static menu from JSON
            $menuPath = config_path('schemas/menu.json');
            $staticMenu = [];

            if (file_exists($menuPath)) {
                $menuData = json_decode(file_get_contents($menuPath), true);
                $staticMenu = $menuData['admin_menu'] ?? [];
            }

            // Get module menus from active modules
            $moduleMenus = $this->getModuleMenus();

            // Merge static and module menus
            return array_merge($staticMenu, $moduleMenus);
        });
    }

    /**
     * Get menu items from all active modules
     */
    public function getModuleMenus(): array
    {
        $menus = [];

        // Get all modules
        $modules = Module::allEnabled();

        foreach ($modules as $module) {
            $moduleName = $module->getName();
            $moduleMenu = $this->getModuleMenu($moduleName);

            if (!empty($moduleMenu)) {
                $menus[] = $moduleMenu;
            }
        }

        return $menus;
    }

    /**
     * Get menu configuration from a specific module
     */
    protected function getModuleMenu(string $moduleName): ?array
    {
        $module = Module::find($moduleName);

        if (!$module) {
            return null;
        }

        // Check if module has menu configuration
        $menuConfigPath = $module->getPath() . '/Config/menu.php';

        if (file_exists($menuConfigPath)) {
            $menuConfig = require $menuConfigPath;

            // Only return if show_in_menu is true
            if (isset($menuConfig['show_in_menu']) && $menuConfig['show_in_menu'] === true) {
                return [
                    'section' => $menuConfig['section'] ?? $moduleName,
                    'items' => $menuConfig['items'] ?? []
                ];
            }
        }

        return null;
    }

    /**
     * Check if a module's menu should be displayed
     */
    public function shouldDisplayModuleMenu(string $moduleName): bool
    {
        $module = Module::find($moduleName);

        if (!$module || !$module->isEnabled()) {
            return false;
        }

        $menuConfigPath = $module->getPath() . '/Config/menu.php';

        if (!file_exists($menuConfigPath)) {
            return false;
        }

        $menuConfig = require $menuConfigPath;

        return isset($menuConfig['show_in_menu']) && $menuConfig['show_in_menu'] === true;
    }
}
