<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Template extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'use_slug_prefix',
        'url_segment',
        'table_name',
        'model_class',
        'description',
        'has_physical_file',
        'requires_database',
        'has_seo',
        'file_path',
        'render_mode',
        'html_content',
        'is_active',
        'is_public',
        'is_system',
        'show_in_menu',
        'menu_label',
        'menu_icon',
        'menu_order',
        'settings',
        // Family & Hierarchy
        'allow_children',
        'allow_new_pages',
        'allowed_parent_templates',
        'allowed_child_templates',
        // Access
        'use_custom_access',
        'allowed_roles',
        // Visual
        'icon',
        // Tree Structure
        'parent_id',
        'tree_level',
        'tree_path',
    ];

    protected $casts = [
        'has_physical_file' => 'boolean',
        'requires_database' => 'boolean',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'is_system' => 'boolean',
        'show_in_menu' => 'boolean',
        'use_slug_prefix' => 'boolean',
        'settings' => 'array',
        'allow_children' => 'boolean',
        'allow_new_pages' => 'boolean',
        'allowed_parent_templates' => 'array',
        'allowed_child_templates' => 'array',
        'use_custom_access' => 'boolean',
        'allowed_roles' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });

        static::saved(function ($template) {
            // Clear template-related caches when template is saved
            \App\Services\CacheInvalidator::clearTemplate($template->id);
        });

        static::deleting(function ($template) {
            // Delete physical file if exists
            if ($template->has_physical_file && $template->file_path) {
                $fullPath = resource_path('views/' . $template->file_path);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            // Clear template-related caches
            \App\Services\CacheInvalidator::clearTemplate($template->id);
        });
    }

    public function fields()
    {
        return $this->hasMany(TemplateField::class)->orderBy('order');
    }

    public function parent()
    {
        return $this->belongsTo(Template::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Template::class, 'parent_id')->orderBy('name');
    }

    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    public function createPhysicalFile(): bool
    {
        if (!$this->has_physical_file || !$this->file_path) {
            return false;
        }

        // If template uses slug prefix, create both index (plural) and single (singular) files
        if ($this->use_slug_prefix) {
            return $this->createIndexAndSingleFiles();
        }

        // Otherwise, create single file as normal
        $fullPath = $this->getPhysicalFilePath();
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = $this->html_content ?: $this->getDefaultTemplateContent();

        return file_put_contents($fullPath, $content) !== false;
    }

    /**
     * Create both index (plural) and single (singular) blade files for slug-prefixed templates
     */
    protected function createIndexAndSingleFiles(): bool
    {
        if (!$this->file_path) {
            return false;
        }

        $basePath = dirname($this->getPhysicalFilePath());

        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Create index file (plural - e.g., templates/services.blade.php)
        $indexPath = $this->getPhysicalFilePath();
        $indexContent = $this->getDefaultIndexTemplateContent();
        $indexCreated = file_put_contents($indexPath, $indexContent) !== false;

        // Create single entry file (singular - e.g., templates/service.blade.php)
        $singlePath = $this->getSingularFilePath();
        $singleContent = $this->html_content ?: $this->getDefaultTemplateContent();
        $singleCreated = file_put_contents($singlePath, $singleContent) !== false;

        return $indexCreated && $singleCreated;
    }

    /**
     * Get the singular file path for single entry view
     */
    protected function getSingularFilePath(): string
    {
        $filePath = $this->file_path;

        // Remove .blade.php extension
        $pathWithoutExt = str_replace('.blade.php', '', $filePath);

        // Split by directory separator
        $parts = explode('/', $pathWithoutExt);
        $lastPart = array_pop($parts);

        // Convert to singular using Laravel's Str helper
        $singularPart = Str::singular($lastPart);

        $parts[] = $singularPart;
        $singularPath = implode('/', $parts) . '.blade.php';

        return resource_path('views/' . $singularPath);
    }

    /**
     * Get the physical file path, ensuring plural form when using slug prefix
     */
    public function getPhysicalFilePath(): string
    {
        if (!$this->file_path) {
            return resource_path('views/');
        }

        // If using slug prefix, ensure the filename is plural
        if ($this->use_slug_prefix) {
            $pathWithoutExt = str_replace('.blade.php', '', $this->file_path);
            $parts = explode('/', $pathWithoutExt);
            $lastPart = array_pop($parts);

            // Convert to plural using Laravel's Str helper
            $pluralPart = Str::plural($lastPart);

            $parts[] = $pluralPart;
            $pluralPath = implode('/', $parts) . '.blade.php';

            return resource_path('views/' . $pluralPath);
        }

        return resource_path('views/' . $this->file_path);
    }

    protected function getDefaultIndexTemplateContent(): string
    {
        return <<<'BLADE'
@extends('frontend.layout')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-8">{{ $title }}</h1>

    {{-- Display all entries with pagination --}}
    @if($entries->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($entries as $entry)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                    @if(isset($entry->image) && $entry->image)
                        <img src="{{ asset('storage/' . $entry->image) }}" alt="{{ $entry->title }}" class="w-full h-48 object-cover">
                    @endif

                    <div class="p-6">
                        <h2 class="text-xl font-semibold mb-2">{{ $entry->title }}</h2>

                        @if(isset($entry->excerpt) && $entry->excerpt)
                            <p class="text-gray-600 mb-4">{{ $entry->excerpt }}</p>
                        @elseif(isset($entry->description) && $entry->description)
                            <p class="text-gray-600 mb-4">{{ Str::limit($entry->description, 150) }}</p>
                        @endif

                        @if(isset($entry->slug) && $entry->slug)
                            <a href="/{{ $template->slug }}/{{ $entry->slug }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                Read More →
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $entries->links() }}
        </div>
    @else
        <p class="text-gray-600">No entries found.</p>
    @endif
</div>
@endsection
BLADE;
    }

    protected function getDefaultTemplateContent(): string
    {
        return <<<'BLADE'
@extends('frontend.layout')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-6">{{ $content->title ?? $title }}</h1>

    {{-- All template fields are available as properties on $content --}}
    {{-- Example: $content->field_name --}}

    @if($content)
        <div class="prose max-w-none">
            {{-- Add your template fields here --}}
            {{-- For example: --}}
            {{-- <p>{{ $content->description }}</p> --}}
        </div>
    @endif
</div>
@endsection
BLADE;
    }
}
