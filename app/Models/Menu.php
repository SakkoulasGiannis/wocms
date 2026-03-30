<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Menu extends Model
{
    protected $fillable = ['name', 'slug', 'location', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($menu) {
            if (empty($menu->slug)) {
                $menu->slug = Str::slug($menu->name);
            }
        });
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('frontend_menus');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('frontend_menus');
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }

    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('order');
    }

    public function getTree(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->rootItems()
            ->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('order')
                    ->with(['children' => function ($q) {
                        $q->where('is_active', true)->orderBy('order');
                    }]);
            }])
            ->get();
    }
}
