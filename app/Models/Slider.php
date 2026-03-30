<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends Model
{
    use SoftDeletes;

    protected $table = 'sliders';

    protected $fillable = [
        'name', 'slug', 'description', 'is_active', 'render_mode', 'status', 'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Check if Active is true
     */
    public function isIsActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Toggle Active
     */
    public function toggleIsActive(): bool
    {
        $this->is_active = ! $this->is_active;
        $this->save();

        return $this->is_active;
    }

    /**
     * Scope a query to only include active (published) entries
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->where('status', 'published')
                    ->orWhereNull('status');
            });
    }
}
