<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Library of reusable card designs for {@see \App\Services\TokenSubstitution}
 * and the SectionEmbed wysiwyg block.
 *
 * A row is essentially a saved HTML snippet with {token} placeholders. The
 * SectionEmbed block can either reference a card by slug (library) or embed
 * its HTML inline; this model is the library storage.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string $html
 * @property string|null $source_template_slug
 * @property string|null $category
 * @property bool $is_system
 * @property int $sort_order
 */
class CardTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'html',
        'source_template_slug',
        'category',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Cards available when the user is picking the design for a given source.
     * A NULL source_template_slug means "generic, works everywhere".
     */
    public static function availableFor(?string $sourceTemplateSlug): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->where(function ($q) use ($sourceTemplateSlug) {
                $q->whereNull('source_template_slug');
                if ($sourceTemplateSlug) {
                    $q->orWhere('source_template_slug', $sourceTemplateSlug);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
