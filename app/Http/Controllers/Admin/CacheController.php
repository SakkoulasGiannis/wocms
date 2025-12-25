<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheInvalidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CacheController extends Controller
{
    /**
     * Clear all application caches
     */
    public function clearAll()
    {
        try {
            // Clear application cache
            CacheInvalidator::clearAll();

            // Clear compiled views
            Artisan::call('view:clear');

            // Clear route cache
            Artisan::call('route:clear');

            // Clear config cache
            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => '✅ All caches cleared successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Error clearing cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear specific cache type
     */
    public function clearType(Request $request)
    {
        $type = $request->input('type');

        try {
            switch ($type) {
                case 'templates':
                    CacheInvalidator::clearTemplate();
                    $message = 'Template caches cleared';
                    break;

                case 'content':
                    CacheInvalidator::clearContentNode();
                    $message = 'Content node caches cleared';
                    break;

                case 'settings':
                    CacheInvalidator::clearSettings();
                    $message = 'Settings caches cleared';
                    break;

                case 'menu':
                    CacheInvalidator::clearMenu();
                    $message = 'Menu caches cleared';
                    break;

                case 'views':
                    Artisan::call('view:clear');
                    $message = 'View caches cleared';
                    break;

                case 'routes':
                    Artisan::call('route:clear');
                    $message = 'Route caches cleared';
                    break;

                case 'config':
                    Artisan::call('config:clear');
                    $message = 'Config caches cleared';
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid cache type'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "✅ {$message}"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cache statistics
     */
    public function stats()
    {
        try {
            $stats = CacheInvalidator::getStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
