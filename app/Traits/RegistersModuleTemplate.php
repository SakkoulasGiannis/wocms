<?php

namespace App\Traits;

use App\Models\Template;
use App\Models\TemplateField;
use Illuminate\Support\Facades\Schema;

/**
 * Trait for module ServiceProviders to auto-register templates on boot.
 *
 * Usage in a module ServiceProvider:
 *
 * use App\Traits\RegistersModuleTemplate;
 *
 * class SliderServiceProvider extends ServiceProvider
 * {
 *     use RegistersModuleTemplate;
 *
 *     protected function templateDefinition(): array
 *     {
 *         return [
 *             'template' => [...],
 *             'fields' => [...],
 *         ];
 *     }
 * }
 */
trait RegistersModuleTemplate
{
    protected function registerTemplate(): void
    {
        // Skip if templates table doesn't exist yet (fresh install, pre-migration)
        if (! Schema::hasTable('templates')) {
            return;
        }

        $definition = $this->templateDefinition();

        if (empty($definition)) {
            return;
        }

        $templateData = $definition['template'];
        $fieldsData = $definition['fields'] ?? [];

        // Only create if it doesn't exist - avoids DB hit on every request
        $template = Template::where('slug', $templateData['slug'])->first();

        if (! $template) {
            $template = Template::create($templateData);

            foreach ($fieldsData as $order => $field) {
                TemplateField::create(array_merge($field, [
                    'template_id' => $template->id,
                    'order' => $order,
                ]));
            }
        }
    }
}
