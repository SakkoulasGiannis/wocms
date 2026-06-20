<?php

namespace App\VisualBuilder;

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
}
