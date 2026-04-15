<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SectionTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'thumbnail',
        'html_template',
        'blade_file',
        'is_system',
        'is_active',
        'order',
        'default_settings',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'default_settings' => 'array',
    ];

    /**
     * Get the fields for this section template
     */
    public function fields()
    {
        return $this->hasMany(SectionTemplateField::class)->orderBy('order');
    }

    /**
     * Get page sections using this template
     */
    public function pageSections()
    {
        return $this->hasMany(PageSection::class);
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return [
            'hero' => 'Hero Sections',
            'content' => 'Content Sections',
            'features' => 'Features & Benefits',
            'testimonials' => 'Testimonials & Reviews',
            'cta' => 'Call to Action',
            'gallery' => 'Gallery & Portfolio',
            'team' => 'Team & About',
            'pricing' => 'Pricing Tables',
            'forms' => 'Forms & Contact',
            'blog' => 'Blog & News',
            'footer' => 'Footer Sections',
            'custom' => 'Custom Sections',
        ];
    }

    /**
     * Render the template with provided data.
     * Supports {{variable}} placeholders and {{#each items}}...{{/each}} blocks.
     */
    public function render(array $data = []): string
    {
        $html = $this->html_template;

        // 1. Process {{#each key}}...{{/each}} blocks for arrays/repeaters
        $html = preg_replace_callback(
            '/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s',
            function ($matches) use ($data) {
                $key = $matches[1];
                $itemTemplate = $matches[2];
                $items = $data[$key] ?? [];

                if (! is_array($items)) {
                    // Try to decode if it's a JSON string
                    $decoded = json_decode($items, true);
                    $items = is_array($decoded) ? $decoded : [];
                }

                $output = '';
                foreach ($items as $item) {
                    $rendered = $itemTemplate;
                    if (is_array($item)) {
                        foreach ($item as $itemKey => $itemValue) {
                            if (! is_array($itemValue)) {
                                $rendered = str_replace('{{this.'.$itemKey.'}}', (string) $itemValue, $rendered);
                            }
                        }
                    }
                    // Remove unreplaced {{this.*}} placeholders
                    $rendered = preg_replace('/\{\{this\.[^}]+\}\}/', '', $rendered);
                    $output .= $rendered;
                }

                return $output;
            },
            $html
        );

        // 2. Replace simple {{variable}} placeholders
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                continue; // Arrays are handled by {{#each}} above
            }

            $html = str_replace('{{'.$key.'}}', (string) $value, $html);
        }

        // 3. Remove any unreplaced placeholders
        $html = preg_replace('/\{\{[^}]+\}\}/', '', $html);

        return $html;
    }

    /**
     * Create or update physical blade file
     */
    public function createBladeFile(): string
    {
        $directory = resource_path('views/sections/templates');

        // Create directory if it doesn't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Generate filename
        $filename = $this->is_system
            ? "{$this->slug}.blade.php"
            : "custom-{$this->id}.blade.php";

        $filePath = "{$directory}/{$filename}";

        // Write template content
        File::put($filePath, $this->html_template);

        // Update blade_file path
        $this->update(['blade_file' => "sections.templates.{$this->getBladeViewName()}"]);

        return $filePath;
    }

    /**
     * Delete physical blade file
     */
    public function deleteBladeFile(): bool
    {
        if (! $this->blade_file) {
            return true;
        }

        $directory = resource_path('views/sections/templates');
        $filename = $this->is_system
            ? "{$this->slug}.blade.php"
            : "custom-{$this->id}.blade.php";

        $filePath = "{$directory}/{$filename}";

        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return true;
    }

    /**
     * Get blade view name
     */
    public function getBladeViewName(): string
    {
        return $this->is_system ? $this->slug : "custom-{$this->id}";
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug on create
        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });

        // Prevent deletion of system templates
        static::deleting(function ($template) {
            if ($template->is_system) {
                throw new \Exception("Cannot delete system section template '{$template->name}'. System templates are protected.");
            }

            // Delete blade file for custom templates
            $template->deleteBladeFile();
        });
    }
}
