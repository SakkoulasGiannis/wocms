<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ModuleController extends Controller
{
    /**
     * Display all modules
     */
    public function index()
    {
        $modules = Module::all();

        $moduleData = [];
        foreach ($modules as $module) {
            $moduleData[] = [
                'name' => $module->getName(),
                'alias' => $module->getLowerName(),
                'description' => $module->getDescription(),
                'enabled' => $module->isEnabled(),
                'path' => $module->getPath(),
                'priority' => $module->get('priority', 0),
                'has_menu' => $this->moduleHasMenu($module->getName()),
            ];
        }

        // Sort by priority
        usort($moduleData, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return view('admin.modules.index', [
            'modules' => $moduleData
        ]);
    }

    /**
     * Enable a module
     */
    public function enable($moduleName)
    {
        try {
            $module = Module::find($moduleName);

            if (!$module) {
                return redirect()->back()->with('error', 'Module not found.');
            }

            Module::enable($moduleName);

            return redirect()->back()->with('success', "Module '{$moduleName}' enabled successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to enable module: ' . $e->getMessage());
        }
    }

    /**
     * Disable a module
     */
    public function disable($moduleName)
    {
        try {
            $module = Module::find($moduleName);

            if (!$module) {
                return redirect()->back()->with('error', 'Module not found.');
            }

            Module::disable($moduleName);

            return redirect()->back()->with('success', "Module '{$moduleName}' disabled successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to disable module: ' . $e->getMessage());
        }
    }

    /**
     * Delete a module
     */
    public function delete($moduleName)
    {
        try {
            $module = Module::find($moduleName);

            if (!$module) {
                return redirect()->back()->with('error', 'Module not found.');
            }

            // Disable first
            Module::disable($moduleName);

            // Delete module directory
            $modulePath = $module->getPath();
            if (File::exists($modulePath)) {
                File::deleteDirectory($modulePath);
            }

            return redirect()->back()->with('success', "Module '{$moduleName}' deleted successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete module: ' . $e->getMessage());
        }
    }

    /**
     * Upload and install a module from ZIP
     */
    public function upload(Request $request)
    {
        $request->validate([
            'module_zip' => 'required|file|mimes:zip|max:51200', // Max 50MB
        ]);

        try {
            $file = $request->file('module_zip');
            $tempPath = storage_path('app/temp-module.zip');

            // Move uploaded file
            $file->move(storage_path('app'), 'temp-module.zip');

            // Extract ZIP
            $zip = new ZipArchive;
            if ($zip->open($tempPath) === TRUE) {
                // Get the first folder name in the ZIP (module name)
                $moduleName = null;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $parts = explode('/', $filename);
                    if (count($parts) > 0 && !empty($parts[0])) {
                        $moduleName = $parts[0];
                        break;
                    }
                }

                if (!$moduleName) {
                    throw new \Exception('Invalid module structure. Could not find module name.');
                }

                // Check if module already exists
                $modulesPath = base_path('Modules');
                if (!File::exists($modulesPath)) {
                    File::makeDirectory($modulesPath, 0755, true);
                }

                $targetPath = $modulesPath . '/' . $moduleName;
                if (File::exists($targetPath)) {
                    throw new \Exception("Module '{$moduleName}' already exists. Please delete it first.");
                }

                // Extract to Modules directory
                $zip->extractTo($modulesPath);
                $zip->close();

                // Clean up temp file
                File::delete($tempPath);

                // Verify module.json exists
                if (!File::exists($targetPath . '/module.json')) {
                    File::deleteDirectory($targetPath);
                    throw new \Exception('Invalid module. Missing module.json file.');
                }

                return redirect()->back()->with('success', "Module '{$moduleName}' installed successfully. You can now enable it.");
            } else {
                throw new \Exception('Failed to open ZIP file.');
            }
        } catch (\Exception $e) {
            // Clean up on error
            if (isset($tempPath) && File::exists($tempPath)) {
                File::delete($tempPath);
            }

            return redirect()->back()->with('error', 'Failed to install module: ' . $e->getMessage());
        }
    }

    /**
     * Check if a module has menu configuration
     */
    protected function moduleHasMenu(string $moduleName): bool
    {
        $module = Module::find($moduleName);
        if (!$module) {
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
