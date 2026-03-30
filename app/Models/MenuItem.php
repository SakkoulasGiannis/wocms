<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'title', 'url', 'type',
        'linkable_type', 'linkable_id', 'target', 'icon',
        'css_class', 'order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::saved(function ($item) {
            \Illuminate\Support\Facades\Cache::forget('frontend_menus');
        });
        static::deleted(function ($item) {
            \Illuminate\Support\Facades\Cache::forget('frontend_menus');
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getResolvedUrlAttribute(): string
    {
        return match ($this->type) {
            'homepage' => url('/'),
            'custom' => $this->url ?? '#',
            'template' => $this->resolveTemplateUrl(),
            'entry' => $this->resolveEntryUrl(),
            default => $this->url ?? '#',
        };
    }

    protected function resolveTemplateUrl(): string
    {
        if ($this->linkable_type && $this->linkable_id) {
            $template = Template::find($this->linkable_id);
            if ($template) {
                return url('/'.$template->slug);
            }
        }

        return $this->url ?? '#';
    }

    protected function resolveEntryUrl(): string
    {
        if ($this->url) {
            return $this->url;
        }

        return '#';
    }
}
