<?php

namespace App\Services;

use App\Models\Template;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class TemplateTableGenerator
{
    /**
     * Create database table and model for template
     */
    public function createTableAndModel(Template $template): bool
    {
        try {
            // Skip if template doesn't require database
            if (!$template->requires_database) {
                \Log::info("Template '{$template->name}' doesn't require database, skipping table/model generation.");
                return true;
            }

            // Generate table name from slug (e.g., 'blog-post' -> 'blog_posts')
            $tableName = Str::plural(str_replace('-', '_', $template->slug));

            // Generate model class name (e.g., 'blog-post' -> 'BlogPost')
            $modelClass = Str::studly(Str::singular($template->slug));

            // Update template with table and model info if not set
            if (!$template->table_name || !$template->model_class) {
                $template->update([
                    'table_name' => $tableName,
                    'model_class' => $modelClass,
                ]);
            }

            // Create or update the database table
            if (Schema::hasTable($tableName)) {
                $this->syncTableColumns($template, $tableName);
            } else {
                $this->createTable($template, $tableName);
            }

            // Generate/update the model file
            $this->createModel($template, $modelClass, $tableName);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to create table/model for template: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync table columns with template fields
     */
    protected function syncTableColumns(Template $template, string $tableName): void
    {
        $existingColumns = Schema::getColumnListing($tableName);
        $templateFields = $template->fields->pluck('name')->toArray();

        // Add CSS columns for grapejs fields to the expected fields list
        $expectedColumns = $templateFields;
        foreach ($template->fields as $field) {
            if ($field->type === 'grapejs') {
                $expectedColumns[] = $field->name . '_css';
            }
        }

        // Protected columns that should never be dropped
        $protectedColumns = ['id', 'render_mode', 'status', 'created_at', 'updated_at', 'deleted_at'];

        // Add SEO columns to protected list if template has SEO
        if ($template->has_seo) {
            $seoColumns = [
                'seo_title', 'seo_description', 'seo_keywords', 'seo_canonical_url', 'seo_focus_keyword',
                'seo_robots_index', 'seo_robots_follow',
                'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_og_type', 'seo_og_url',
                'seo_twitter_card', 'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_image',
                'seo_twitter_site', 'seo_twitter_creator',
                'seo_schema_type', 'seo_schema_custom',
                'seo_redirect_url', 'seo_redirect_type', 'seo_sitemap_include', 'seo_sitemap_priority', 'seo_sitemap_changefreq'
            ];
            $protectedColumns = array_merge($protectedColumns, $seoColumns);
        }

        // First, drop removed columns
        $columnsToDelete = [];
        foreach ($existingColumns as $columnName) {
            if (!in_array($columnName, $expectedColumns) && !in_array($columnName, $protectedColumns)) {
                $columnsToDelete[] = $columnName;
            }
        }

        if (!empty($columnsToDelete)) {
            Schema::table($tableName, function (Blueprint $table) use ($columnsToDelete) {
                foreach ($columnsToDelete as $columnName) {
                    try {
                        $table->dropColumn($columnName);
                        \Log::info("Dropped column: {$columnName}");
                    } catch (\Exception $e) {
                        \Log::error("Failed to drop column {$columnName}: " . $e->getMessage());
                    }
                }
            });
        }

        // Then, add new columns with proper positioning
        Schema::table($tableName, function (Blueprint $table) use ($template, $existingColumns) {
            $previousFieldName = 'id'; // Start after ID column
            foreach ($template->fields->sortBy('order') as $field) {
                if (!in_array($field->name, $existingColumns)) {
                    try {
                        $this->addColumnForField($table, $field, $previousFieldName);
                        \Log::info("Added column: {$field->name}");
                    } catch (\Exception $e) {
                        \Log::error("Failed to add column {$field->name}: " . $e->getMessage());
                    }
                }
                // For grapejs fields, the CSS column is created automatically, so update the previousFieldName accordingly
                if ($field->type === 'grapejs') {
                    $previousFieldName = $field->name . '_css';
                } else {
                    $previousFieldName = $field->name;
                }
            }
        });
    }

    /**
     * Create database table based on template fields
     */
    protected function createTable(Template $template, string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($template) {
            $table->id();

            // Add render_mode column for all dynamic tables (controls how entry is rendered)
            $table->string('render_mode', 50)->default('full_page_grapejs');

            // Add status column for all dynamic tables (active, draft, disabled)
            $table->string('status', 20)->default('active')->comment('Status: active, draft, disabled');

            // Add columns based on template fields in order
            // Note: Don't use ->after() in CREATE TABLE, only in ALTER TABLE
            foreach ($template->fields->sortBy('order') as $field) {
                $this->addColumnForField($table, $field, null); // Pass null for afterColumn in CREATE
            }

            // Add SEO fields if template has SEO enabled
            if ($template->has_seo) {
                $this->addSeoFields($table);
            }

            // Add standard timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Add column to table based on field type
     */
    protected function addColumnForField(Blueprint $table, $field, string $afterColumn = null): void
    {
        $columnName = $field->name;

        switch ($field->type) {
            case 'text':
            case 'email':
            case 'url':
                $column = $table->string($columnName)->nullable();
                break;

            case 'textarea':
                $column = $table->text($columnName)->nullable();
                break;

            case 'wysiwyg':
                $column = $table->longText($columnName)->nullable();
                break;

            case 'grapejs':
                // For GrapeJS, create two columns: one for HTML and one for CSS
                $column = $table->longText($columnName)->nullable();

                // Position the HTML column (only in ALTER TABLE, not CREATE TABLE)
                if ($afterColumn) {
                    $column->after($afterColumn);
                }

                // Add default value if specified
                if ($field->default_value) {
                    $column->default($field->default_value);
                }

                // Create the CSS column right after the HTML column
                $cssColumn = $table->longText($columnName . '_css')->nullable();

                // Only use ->after() in ALTER TABLE operations (when $afterColumn is provided)
                // In CREATE TABLE, columns are created in order, so no ->after() is needed
                if ($afterColumn) {
                    $cssColumn->after($columnName);
                }

                // Return early since we've handled positioning and defaults
                return;

            case 'number':
            case 'integer':
                $column = $table->integer($columnName)->nullable();
                break;

            case 'decimal':
            case 'float':
                $column = $table->decimal($columnName, 10, 2)->nullable();
                break;

            case 'boolean':
            case 'checkbox':
                $column = $table->boolean($columnName)->default(false);
                break;

            case 'date':
                $column = $table->date($columnName)->nullable();
                break;

            case 'time':
                $column = $table->time($columnName)->nullable();
                break;

            case 'datetime':
                $column = $table->dateTime($columnName)->nullable();
                break;

            case 'select':
                $column = $table->string($columnName)->nullable();
                break;

            case 'group':
                $column = $table->json($columnName)->nullable();
                break;

            case 'json':
            case 'repeater':
            case 'gallery':
                $column = $table->json($columnName)->nullable();
                break;

            case 'relation':
                // Check if multiple (hasMany/belongsToMany) - use JSON
                // Otherwise use integer for foreign key (belongsTo)
                $settings = is_string($field->settings) ? json_decode($field->settings, true) : ($field->settings ?? []);
                $relationType = $settings['type'] ?? 'belongsTo';

                if (in_array($relationType, ['hasMany', 'belongsToMany'])) {
                    $column = $table->json($columnName)->nullable();
                } else {
                    $column = $table->unsignedBigInteger($columnName)->nullable();
                }
                break;

            default:
                $column = $table->text($columnName)->nullable();
        }

        // Position the column after the specified column
        if ($afterColumn) {
            $column->after($afterColumn);
        }

        // Add default value if specified
        if ($field->default_value) {
            $column->default($field->default_value);
        }
    }

    /**
     * Create model class file
     */
    protected function createModel(Template $template, string $modelClass, string $tableName): void
    {
        $modelPath = app_path("Models/{$modelClass}.php");

        // Always regenerate the model to include updated methods
        // if (File::exists($modelPath)) {
        //     return; // Model already exists
        // }

        // Get fillable fields (including CSS columns for grapejs fields)
        $fillable = [];
        foreach ($template->fields as $field) {
            $fillable[] = $field->name;
            if ($field->type === 'grapejs') {
                $fillable[] = $field->name . '_css';
            }
        }

        // Add SEO fields if template has SEO
        if ($template->has_seo) {
            $fillable = array_merge($fillable, [
                'seo_title', 'seo_description', 'seo_keywords', 'seo_canonical_url', 'seo_focus_keyword',
                'seo_robots_index', 'seo_robots_follow',
                'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_og_type', 'seo_og_url',
                'seo_twitter_card', 'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_image',
                'seo_twitter_site', 'seo_twitter_creator',
                'seo_schema_type', 'seo_schema_custom',
                'seo_redirect_url', 'seo_redirect_type', 'seo_sitemap_include', 'seo_sitemap_priority', 'seo_sitemap_changefreq'
            ]);
        }

        // Add system fields (render_mode, status, created_at for custom setting)
        $fillable[] = 'render_mode';
        $fillable[] = 'status';
        $fillable[] = 'created_at';

        $fillableString = "'" . implode("', '", $fillable) . "'";

        // Get fields that should be cast
        $casts = [];
        foreach ($template->fields as $field) {
            switch ($field->type) {
                case 'boolean':
                case 'checkbox':
                    $casts[$field->name] = 'boolean';
                    break;
                case 'integer':
                case 'number':
                    $casts[$field->name] = 'integer';
                    break;
                case 'decimal':
                case 'float':
                    $casts[$field->name] = 'float';
                    break;
                case 'date':
                    $casts[$field->name] = 'date';
                    break;
                case 'datetime':
                    $casts[$field->name] = 'datetime';
                    break;
                case 'json':
                case 'repeater':
                case 'gallery':
                case 'group':
                    $casts[$field->name] = 'array';
                    break;
                case 'relation':
                    // Only cast to array if it's a multiple relationship
                    $settings = is_string($field->settings) ? json_decode($field->settings, true) : ($field->settings ?? []);
                    $relationType = $settings['type'] ?? 'belongsTo';
                    if (in_array($relationType, ['hasMany', 'belongsToMany'])) {
                        $casts[$field->name] = 'array';
                    }
                    break;
            }
        }

        $castsString = '';
        if (!empty($casts)) {
            $castLines = [];
            foreach ($casts as $key => $type) {
                $castLines[] = "        '{$key}' => '{$type}'";
            }
            $castsString = "\n\n    protected \$casts = [\n" . implode(",\n", $castLines) . ",\n    ];";
        }

        // Generate methods using ModelMethodsGenerator
        $methodsGenerator = new ModelMethodsGenerator();
        $generatedMethods = $methodsGenerator->generateMethods($template);

        // Add active() scope for published content filtering
        $activeScope = <<<'PHP'

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
PHP;
        $generatedMethods .= $activeScope;

        // Check if template has image fields for Spatie Media Library
        $hasImageFields = $template->fields->whereIn('type', ['image', 'gallery'])->count() > 0;

        $mediaImport = $hasImageFields ? "\nuse Spatie\MediaLibrary\HasMedia;\nuse Spatie\MediaLibrary\InteractsWithMedia;" : '';
        $mediaTrait = $hasImageFields ? ", InteractsWithMedia" : '';
        $mediaImplements = $hasImageFields ? " implements HasMedia" : '';

        $modelContent = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;{$mediaImport}

class {$modelClass} extends Model{$mediaImplements}
{
    use SoftDeletes{$mediaTrait};

    protected \$table = '{$tableName}';

    protected \$fillable = [
        {$fillableString}
    ];{$castsString}{$generatedMethods}
}
PHP;

        File::put($modelPath, $modelContent);
    }

    /**
     * Drop table and delete model for template
     */
    public function dropTableAndModel(Template $template): bool
    {
        try {
            // Drop table if exists
            if ($template->table_name && Schema::hasTable($template->table_name)) {
                Schema::dropIfExists($template->table_name);
            }

            // Delete model file if exists
            if ($template->model_class) {
                $modelPath = app_path("Models/{$template->model_class}.php");
                if (File::exists($modelPath)) {
                    File::delete($modelPath);
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to drop table/model for template: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add comprehensive SEO fields to table
     */
    protected function addSeoFields(Blueprint $table): void
    {
        // Basic SEO
        $table->string('seo_title', 70)->nullable()->comment('Meta title (max 60-70 chars)');
        $table->string('seo_description', 160)->nullable()->comment('Meta description (max 155-160 chars)');
        $table->string('seo_keywords')->nullable()->comment('Meta keywords (comma-separated)');
        $table->string('seo_canonical_url')->nullable()->comment('Canonical URL');
        $table->string('seo_focus_keyword')->nullable()->comment('Focus/target keyword');

        // Robots
        $table->string('seo_robots_index', 20)->default('index')->comment('index or noindex');
        $table->string('seo_robots_follow', 20)->default('follow')->comment('follow or nofollow');

        // Open Graph (Facebook)
        $table->string('seo_og_title')->nullable()->comment('Open Graph title');
        $table->text('seo_og_description')->nullable()->comment('Open Graph description');
        $table->string('seo_og_image')->nullable()->comment('Open Graph image URL');
        $table->string('seo_og_type', 50)->default('website')->comment('Open Graph type (website, article, etc.)');
        $table->string('seo_og_url')->nullable()->comment('Open Graph URL');

        // Twitter Card
        $table->string('seo_twitter_card', 50)->default('summary_large_image')->comment('Twitter card type');
        $table->string('seo_twitter_title')->nullable()->comment('Twitter card title');
        $table->text('seo_twitter_description')->nullable()->comment('Twitter card description');
        $table->string('seo_twitter_image')->nullable()->comment('Twitter card image URL');
        $table->string('seo_twitter_site')->nullable()->comment('Twitter @username for site');
        $table->string('seo_twitter_creator')->nullable()->comment('Twitter @username for creator');

        // Schema.org / Structured Data
        $table->string('seo_schema_type', 50)->nullable()->comment('Schema.org type (Article, BlogPosting, etc.)');
        $table->text('seo_schema_custom')->nullable()->comment('Custom JSON-LD schema markup');

        // Advanced SEO
        $table->string('seo_redirect_url')->nullable()->comment('Redirect URL (301/302)');
        $table->string('seo_redirect_type', 10)->default('301')->comment('Redirect type (301 or 302)');
        $table->boolean('seo_sitemap_include')->default(true)->comment('Include in XML sitemap');
        $table->string('seo_sitemap_priority', 10)->default('0.5')->comment('Sitemap priority (0.0 to 1.0)');
        $table->string('seo_sitemap_changefreq', 20)->default('weekly')->comment('Sitemap change frequency');
    }
}
