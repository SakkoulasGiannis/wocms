<?php

use App\Models\SectionTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Removes the hardcoded `p-2` padding baked into the primitive-div section
 * template. It forced 0.5rem padding onto every div the user created and
 * could not be removed from the visual editor — so user-supplied classes
 * never had full control of the element.
 *
 * After this, primitive-div renders only the classes the user sets:
 *   <div id="{{id}}" class="{{class}}">{{children}}</div>
 *
 * Idempotent: only rewrites if the old template with `p-2 ` is present.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tpl = SectionTemplate::where('slug', 'primitive-div')->first();

        if (! $tpl) {
            return;
        }

        if (str_contains((string) $tpl->html_template, 'class="p-2 {{class}}"')) {
            $tpl->html_template = '<div id="{{id}}" class="{{class}}">{{children}}</div>';
            $tpl->save();
        }
    }

    public function down(): void
    {
        $tpl = SectionTemplate::where('slug', 'primitive-div')->first();

        if ($tpl && $tpl->html_template === '<div id="{{id}}" class="{{class}}">{{children}}</div>') {
            $tpl->html_template = '<div id="{{id}}" class="p-2 {{class}}">{{children}}</div>';
            $tpl->save();
        }
    }
};
