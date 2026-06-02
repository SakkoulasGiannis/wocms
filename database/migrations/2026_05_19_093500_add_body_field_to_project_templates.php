<?php

use App\Models\Template;
use App\Models\TemplateField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a rich-text "body" field (EditorJS / type=wysiwyg) to the three
 * project templates: Completed Villas, Renovations, Under Construction.
 *
 * Two parts, both idempotent:
 *  1. `body` LONGTEXT nullable column on each table (matches how
 *     TemplateTableGenerator maps the wysiwyg type).
 *  2. A `body` TemplateField row per template so the admin entry form
 *     renders the EditorJS editor.
 *
 * NOTE: the generated models' $fillable is NOT auto-updated (createModel
 * never clobbers an existing file), so `'body'` is added to the
 * CompletedVilla/Renovation/UnderConstruction models by hand in the same
 * change — without it, mass-assignment would silently drop the value.
 */
return new class extends Migration
{
    /** @var array<string,string> template slug => table name */
    private array $map = [
        'completed-villas' => 'completed_villas',
        'renovations' => 'renovations',
        'under-construction' => 'under_constructions',
    ];

    public function up(): void
    {
        foreach ($this->map as $slug => $table) {
            // 1. column
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'body')) {
                Schema::table($table, function (Blueprint $t): void {
                    $t->longText('body')->nullable();
                });
            }

            // 2. TemplateField
            $template = Template::where('slug', $slug)->first();
            if (! $template) {
                continue;
            }
            $exists = TemplateField::where('template_id', $template->id)
                ->where('name', 'body')
                ->exists();
            if ($exists) {
                continue;
            }

            TemplateField::create([
                'template_id' => $template->id,
                'name' => 'body',
                'label' => 'Body',
                'type' => 'wysiwyg',
                'description' => 'Free text / rich content',
                'validation_rules' => ['nullable'],
                'default_value' => null,
                'adapts_to_render_mode' => false,
                'settings' => ['sub_fields' => ''],
                'order' => (int) ($template->fields()->max('order') ?? 0) + 1,
                'is_required' => false,
                'is_searchable' => false,
                'is_filterable' => false,
                'is_url_identifier' => false,
                'show_in_table' => false,
                'column_position' => 'main',
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->map as $slug => $table) {
            $template = Template::where('slug', $slug)->first();
            if ($template) {
                TemplateField::where('template_id', $template->id)
                    ->where('name', 'body')
                    ->delete();
            }
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'body')) {
                Schema::table($table, function (Blueprint $t): void {
                    $t->dropColumn('body');
                });
            }
        }
    }
};
