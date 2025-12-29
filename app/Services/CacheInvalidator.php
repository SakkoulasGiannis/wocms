<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\Template;
use App\Models\ContentNode;

class CacheInvalidator
{
    /**
     * Clear template-related caches
     */
    public static function clearTemplate($templateId = null): void
    {
        if ($templateId) {
            $template = Template::find($templateId);
            if ($template) {
                Cache::forget("template.{$template->slug}.with-fields");
                \Log::info("Cleared cache for template: {$template->slug}");
            }
        }

        // Clear general template caches
        Cache::forget('templates.active.all');
        Cache::forget('admin.menu.full'); // Menu might include template-based items

        \Log::info('Cleared general template caches');
    }

    /**
     * Clear content node-related caches
     */
    public static function clearContentNode($nodeId = null): void
    {
        if ($nodeId) {
            $node = ContentNode::find($nodeId);
            if ($node) {
                Cache::forget("content_node.path.{$node->url_path}");
                Cache::forget("node.{$node->id}.breadcrumbs");
                // Clear full page cache
                Cache::forget("page.{$node->url_path}");
                \Log::info("Cleared cache for content node: {$node->url_path}");
            }
        }

        // Clear general content caches
        Cache::forget('content_tree.root_nodes');

        \Log::info('Cleared content node caches');
    }

    /**
     * Clear settings-related caches
     */
    public static function clearSettings($key = null, $group = null): void
    {
        if ($key) {
            Cache::forget("setting.{$key}");
            \Log::info("Cleared cache for setting: {$key}");
        }

        if ($group) {
            Cache::forget("settings.group.{$group}");
            \Log::info("Cleared cache for settings group: {$group}");
        }

        // Clear AI provider cache when settings change
        Cache::forget('ai.provider.active');
        $providers = ['claude', 'chatgpt', 'ollama'];
        foreach ($providers as $provider) {
            Cache::forget("ai.provider.instance.{$provider}");
        }

        \Log::info('Cleared settings caches');
    }

    /**
     * Clear menu-related caches
     */
    public static function clearMenu(): void
    {
        Cache::forget('admin.menu.full');
        \Log::info('Cleared menu caches');
    }

    /**
     * Clear all application caches
     */
    public static function clearAll(): void
    {
        Cache::flush();
        \Log::info('Cleared all application caches');
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        $cacheKeys = [
            'templates.active.all',
            'admin.menu.full',
            'content_tree.root_nodes',
            'ai.provider.active',
        ];

        $stats = [
            'cached_items' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];

        foreach ($cacheKeys as $key) {
            if (Cache::has($key)) {
                $stats['cached_items']++;
            }
        }

        return $stats;
    }
}
