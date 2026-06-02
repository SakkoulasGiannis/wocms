<?php

namespace Modules\PageBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\PageBuilder\Database\Factories\SectionTemplateFactory;

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
    public function fields(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SectionTemplateField::class)->orderBy('order');
    }

    /**
     * Get page sections using this template
     */
    public function pageSections(): \Illuminate\Database\Eloquent\Relations\HasMany
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

            // EditorJS JSON detection: WYSIWYG fields persist their output as
            // {"time":..., "blocks":[...], "version":"..."}. Plain str_replace
            // would dump that raw into the HTML. Detect + render to HTML first.
            $stringValue = (string) $value;
            if ($this->looksLikeEditorJsJson($stringValue)) {
                try {
                    $stringValue = app(\App\Services\EditorJsRenderer::class)->toHtml($stringValue);
                } catch (\Throwable $e) {
                    // If the renderer service isn't available or throws, fall
                    // back to stripping JSON so the user at least sees the text.
                    $decoded = json_decode($stringValue, true);
                    if (is_array($decoded) && isset($decoded['blocks'])) {
                        $stringValue = collect($decoded['blocks'])
                            ->map(fn ($b) => $b['data']['text'] ?? '')
                            ->filter()
                            ->implode(' ');
                    }
                }
            }

            $html = str_replace('{{'.$key.'}}', $stringValue, $html);
        }

        // 3. Remove any unreplaced placeholders
        $html = preg_replace('/\{\{[^}]+\}\}/', '', $html);

        // 4. Tidy attributes left messy by empty token substitutions, e.g.
        //    <div id="" class="p-2 "> or <div class="grid grid-cols- gap- ">.
        //    Keeps the markup lean and avoids invalid dangling Tailwind utilities.
        $html = $this->tidyAttributes($html);

        return $html;
    }

    /**
     * Clean up attributes after placeholder substitution:
     *  - drop empty id="" attributes
     *  - collapse whitespace inside class="" and trim
     *  - strip incomplete Tailwind utilities (tokens ending in '-', e.g.
     *    grid-cols-, gap-, col-span-) caused by an empty {{columns}}/{{gap}}
     *  - drop the class attribute entirely if nothing's left
     */
    protected function tidyAttributes(string $html): string
    {
        // Remove blank id attributes: id="" or id="   "
        $html = preg_replace('/\sid="\s*"/', '', $html);

        // Normalise class attributes
        $html = preg_replace_callback('/\sclass="([^"]*)"/', function (array $m): string {
            $tokens = preg_split('/\s+/', trim($m[1])) ?: [];
            $clean = array_filter($tokens, function (string $t): bool {
                if ($t === '') {
                    return false;
                }

                // Incomplete utility like "grid-cols-", "gap-", "col-span-"
                return ! str_ends_with($t, '-');
            });

            if (empty($clean)) {
                return ''; // remove the whole class="" attribute
            }

            return ' class="'.implode(' ', $clean).'"';
        }, $html);

        return $html;
    }

    /**
     * Heuristic — is this string an EditorJS JSON payload? Avoids running
     * json_decode for every plain text replacement.
     */
    protected function looksLikeEditorJsJson(string $value): bool
    {
        $value = ltrim($value);
        if ($value === '' || $value[0] !== '{') {
            return false;
        }
        if (! str_contains($value, '"blocks"')) {
            return false;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) && isset($decoded['blocks']) && is_array($decoded['blocks']);
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
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SectionTemplateFactory
    {
        return SectionTemplateFactory::new();
    }

    /**
     * Boot method
     */
    protected static function boot(): void
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
