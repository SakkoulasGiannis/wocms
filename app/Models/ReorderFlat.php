<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReorderFlat extends Model
{
    use SoftDeletes;

    protected $table = 'reorder_flats';

    protected $fillable = [
        'title', 'slug', 'render_mode', 'status', 'created_at',
    ];

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
