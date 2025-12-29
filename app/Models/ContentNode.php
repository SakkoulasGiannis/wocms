<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentNode extends Model
{
    use SoftDeletes;

    protected $table = 'content_tree';

    protected $fillable = [
        'template_id',
        'parent_id',
        'content_type',
        'content_id',
        'title',
        'slug',
        'url_path',
        'level',
        'tree_path',
        'is_published',
        'cache_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'cache_enabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($node) {
            // Auto-generate URL path and tree structure
            if (!$node->url_path) {
                $node->generateUrlPath();
            }
            if (!$node->tree_path) {
                $node->generateTreePath();
            }
        });

        static::updating(function ($node) {
            // Update URL path if slug or parent changed
            if ($node->isDirty(['slug', 'parent_id'])) {
                $node->generateUrlPath();
                $node->generateTreePath();

                // Update all children's paths
                $node->updateChildrenPaths();
            }
        });

        static::saved(function ($node) {
            // Clear content node-related caches when node is saved
            \App\Services\CacheInvalidator::clearContentNode($node->id);
        });

        static::deleted(function ($node) {
            // Clear content node-related caches when node is deleted
            \App\Services\CacheInvalidator::clearContentNode($node->id);
        });
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function parent()
    {
        return $this->belongsTo(ContentNode::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ContentNode::class, 'parent_id')->orderBy('sort_order');
    }

    public function content()
    {
        return $this->morphTo();
    }

    /**
     * Get the actual content model instance
     */
    public function getContentModel()
    {
        if ($this->content_type && $this->content_id) {
            return $this->content_type::find($this->content_id);
        }
        return null;
    }

    /**
     * Generate full URL path based on parent structure and template settings
     */
    public function generateUrlPath()
    {
        // Load template if not already loaded
        if (!$this->relationLoaded('template')) {
            $this->load('template');
        }

        // Check if template uses slug prefix
        if ($this->template && $this->template->use_slug_prefix) {
            // Use template slug as prefix: /template-slug/{entry-slug}
            $this->url_path = '/' . $this->template->slug . '/' . $this->slug;
            $this->level = 1; // Template-prefixed entries are level 1
        } elseif (!$this->parent_id) {
            // Root level without prefix
            $this->url_path = '/' . $this->slug;
            $this->level = 0;
        } else {
            // Child level (hierarchical)
            $parent = ContentNode::find($this->parent_id);
            if ($parent) {
                // Handle root parent specially to avoid double slashes
                if ($parent->url_path === '/') {
                    $this->url_path = '/' . $this->slug;
                } else {
                    $this->url_path = rtrim($parent->url_path, '/') . '/' . $this->slug;
                }
                $this->level = $parent->level + 1;
            }
        }
    }

    /**
     * Generate tree path for fast lookups
     */
    public function generateTreePath()
    {
        if (!$this->parent_id) {
            $this->tree_path = '/' . $this->id;
        } else {
            $parent = ContentNode::find($this->parent_id);
            if ($parent) {
                $this->tree_path = $parent->tree_path . '/' . $this->id;
            }
        }
    }

    /**
     * Update all children's URL paths recursively
     */
    public function updateChildrenPaths()
    {
        $children = $this->children()->get();
        foreach ($children as $child) {
            $child->generateUrlPath();
            $child->generateTreePath();
            $child->saveQuietly(); // Save without triggering events
            $child->updateChildrenPaths();
        }
    }

    /**
     * Get breadcrumb trail
     */
    public function breadcrumbs()
    {
        $breadcrumbs = collect([$this]);
        $node = $this;

        while ($node->parent_id) {
            $node = $node->parent;
            $breadcrumbs->prepend($node);
        }

        return $breadcrumbs;
    }

    /**
     * Check if this node allows children
     */
    public function canHaveChildren(): bool
    {
        return $this->template && $this->template->allow_children;
    }

    /**
     * Get allowed child templates for this node
     */
    public function getAllowedChildTemplates()
    {
        if (!$this->template || !$this->template->allowed_child_templates) {
            return collect();
        }

        return Template::whereIn('id', $this->template->allowed_child_templates)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Check if full page caching is enabled for this node
     * Respects both template and per-page settings
     */
    public function isCacheEnabled(): bool
    {
        // If page-level override is set, use it
        if ($this->cache_enabled !== null) {
            return $this->cache_enabled;
        }

        // Otherwise use template setting
        return $this->template && $this->template->enable_full_page_cache;
    }

    /**
     * Get cache TTL for this node (in seconds)
     */
    public function getCacheTtl(): int
    {
        return $this->template->cache_ttl ?? 3600;
    }
}
