<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalProperty extends Model
{
    use SoftDeletes;

    protected $table = 'rental_properties';

    protected $fillable = [
        'title', 'slug', 'description', 'property_type', 'status', 'price', 'city', 'bedrooms', 'area', 'active', 'render_mode', 'status', 'created_at',
    ];

    protected $casts = [
        'price' => 'integer',
        'bedrooms' => 'integer',
        'area' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Check if Active is true
     */
    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    /**
     * Toggle Active
     */
    public function toggleActive(): bool
    {
        $this->active = ! $this->active;
        $this->save();

        return $this->active;
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
