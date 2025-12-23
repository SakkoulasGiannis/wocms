<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $table = 'blog_posts';

    protected $fillable = [
        'title', 'slug', 'excerpt', 'featured_image', 'content', 'content_css', 'author', 'category', 'tags', 'published_date', 'is_published'
    ];

    protected $casts = [
        'published_date' => 'date',
        'is_published' => 'boolean',
    ];
    /**
     * Get formatted Ημερομηνία Δημοσίευσης
     */
    public function getPublishedDateFormatted($format = 'Y-m-d'): ?string
    {
        return $this->published_date ? $this->published_date->format($format) : null;
    }

    /**
     * Get Ημερομηνία Δημοσίευσης for humans
     */
    public function getPublishedDateForHumans(): ?string
    {
        return $this->published_date ? $this->published_date->diffForHumans() : null;
    }

    /**
     * Check if Δημοσιευμένο is true
     */
    public function isIsPublished(): bool
    {
        return (bool) $this->is_published;
    }

    /**
     * Toggle Δημοσιευμένο
     */
    public function toggleIsPublished(): bool
    {
        $this->is_published = !$this->is_published;
        $this->save();
        return $this->is_published;
    }
}