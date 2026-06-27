<?php

namespace App\VisualBuilder;

use App\Models\ContentNode;
use App\Models\Setting;
use App\Models\Template;
use Illuminate\Support\Facades\Schema;
use Weborange\VisualBuilder\Contracts\TokenSource;

/**
 * Host implementation of the visual-builder token source: exposes Templates that
 * map to an Eloquent model as loop sources, and their columns/media as {tokens}.
 */
class CmsTokenSource implements TokenSource
{
    public function sources(): array
    {
        return Template::query()
            ->whereNotNull('model_class')
            ->orderBy('name')
            ->get(['slug', 'name'])
            ->map(fn (Template $t): array => ['slug' => $t->slug, 'name' => $t->name])
            ->values()
            ->all();
    }

    public function tokens(string $source): array
    {
        $template = Template::query()->where('slug', $source)->first();
        if (! $template || ! $template->model_class) {
            return [];
        }

        $modelClass = str_contains($template->model_class, '\\')
            ? $template->model_class
            : 'App\\Models\\'.$template->model_class;

        if (! class_exists($modelClass)) {
            return [];
        }

        $tokens = [];
        try {
            $skip = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];
            foreach (Schema::getColumnListing((new $modelClass)->getTable()) as $col) {
                if (in_array($col, $skip, true)) {
                    continue;
                }
                $tokens[] = ['token' => '{'.$col.'}', 'label' => $col];
            }
            if (is_subclass_of($modelClass, \Spatie\MediaLibrary\HasMedia::class)) {
                $tokens[] = ['token' => '{main_image:preview}', 'label' => 'main_image (preview)'];
                $tokens[] = ['token' => '{main_image:hero}', 'label' => 'main_image (hero)'];
            }
        } catch (\Throwable $e) {
            return $tokens;
        }

        return $tokens;
    }

    public function forms(): array
    {
        return \App\Models\Form::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['slug', 'name'])
            ->map(fn (\App\Models\Form $f): array => ['slug' => $f->slug, 'name' => $f->name])
            ->values()
            ->all();
    }

    public function sliders(): array
    {
        if (! class_exists(\Modules\Slider\Models\Slider::class)) {
            return [];
        }

        return \Modules\Slider\Models\Slider::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (\Modules\Slider\Models\Slider $s): array => ['id' => $s->id, 'name' => $s->name])
            ->values()
            ->all();
    }

    public function entries(string $source): array
    {
        $template = Template::query()->where('slug', $source)->first();
        if (! $template || ! $template->model_class) {
            return [];
        }

        $modelClass = str_contains($template->model_class, '\\')
            ? $template->model_class
            : 'App\\Models\\'.$template->model_class;

        if (! class_exists($modelClass)) {
            return [];
        }

        $model = new $modelClass;
        $table = $model->getTable();
        $pk = $model->getKeyName();
        $labelCol = collect(['title', 'name', 'slug'])->first(fn (string $c): bool => Schema::hasColumn($table, $c)) ?? $pk;

        return $modelClass::query()
            ->orderByDesc($pk)
            ->limit(300)
            ->get()
            ->map(fn ($e): array => ['id' => $e->getKey(), 'label' => (string) ($e->{$labelCol} ?: ('#'.$e->getKey()))])
            ->all();
    }

    public function nodes(): array
    {
        return ContentNode::query()
            ->orderBy('url_path')
            ->get(['id', 'title', 'url_path'])
            ->map(fn (ContentNode $n): array => [
                'id' => $n->id,
                'label' => ($n->title ?: 'Untitled').' — '.($n->url_path ?: '/'),
            ])
            ->all();
    }

    public function siteCss(): string
    {
        return (string) Setting::get('site_custom_css', '');
    }

    public function saveSiteCss(string $css): void
    {
        Setting::set('site_custom_css', $css, 'integrations');
    }

    public function renderSlider(string $id): string
    {
        return app(LoopRenderer::class)->renderSlider($id);
    }

    public function renderLoop(string $source, array $query, string $itemHtml): array
    {
        return app(LoopRenderer::class)->renderItems($source, $query, $itemHtml);
    }
}
