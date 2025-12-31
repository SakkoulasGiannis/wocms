<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use SoftDeletes;

    protected $table = 'sections';

    protected $fillable = [
        'title', 'slug', 'render_mode', 'status', 'created_at'
    ];

    /**
     * Get the page sections for this entry
     */
    public function sections()
    {
        return $this->morphMany(PageSection::class, 'sectionable')->orderBy('order');
    }

    /**
     * Get only active page sections for this entry
     */
    public function activeSections()
    {
        return $this->sections()->where('is_active', true);
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