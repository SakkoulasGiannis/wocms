<?php

use App\Models\SectionTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Strips cosmetic hardcoded classes from the primitive-heading and
 * primitive-paragraph section templates so user-supplied classes have full
 * control of the element (consistent with the earlier primitive-div cleanup).
 *
 *   primitive-heading:  "my-3 {{class}}"  →  "{{class}}"
 *   primitive-paragraph: "{{class}} prose" →  "{{class}}"
 *
 * Functional primitives (grid's `grid grid-cols-* gap-*`, icon's
 * `inline-flex items-center`) are intentionally left alone — those classes
 * are structural, not cosmetic.
 *
 * Idempotent: each rewrite only fires if the old pattern is still present.
 */
return new class extends Migration
{
    public function up(): void
    {
        $heading = SectionTemplate::where('slug', 'primitive-heading')->first();
        if ($heading && str_contains((string) $heading->html_template, 'class="my-3 {{class}}"')) {
            $heading->html_template = '<{{tag}} id="{{id}}" class="{{class}}">{{text}}</{{tag}}>';
            $heading->save();
        }

        $para = SectionTemplate::where('slug', 'primitive-paragraph')->first();
        if ($para && str_contains((string) $para->html_template, 'class="{{class}} prose"')) {
            $para->html_template = '<div id="{{id}}" class="{{class}}">{{content}}</div>';
            $para->save();
        }
    }

    public function down(): void
    {
        $heading = SectionTemplate::where('slug', 'primitive-heading')->first();
        if ($heading && $heading->html_template === '<{{tag}} id="{{id}}" class="{{class}}">{{text}}</{{tag}}>') {
            $heading->html_template = '<{{tag}} id="{{id}}" class="my-3 {{class}}">{{text}}</{{tag}}>';
            $heading->save();
        }

        $para = SectionTemplate::where('slug', 'primitive-paragraph')->first();
        if ($para && $para->html_template === '<div id="{{id}}" class="{{class}}">{{content}}</div>') {
            $para->html_template = '<div id="{{id}}" class="{{class}} prose">{{content}}</div>';
            $para->save();
        }
    }
};
