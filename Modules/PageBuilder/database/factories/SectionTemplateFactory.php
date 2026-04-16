<?php

namespace Modules\PageBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PageBuilder\Models\SectionTemplate;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\PageBuilder\Models\SectionTemplate>
 */
class SectionTemplateFactory extends Factory
{
    protected $model = SectionTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => \Illuminate\Support\Str::slug($name),
            'category' => $this->faker->randomElement(['hero', 'content', 'cta', 'gallery', 'forms']),
            'description' => $this->faker->sentence(),
            'html_template' => '<div>{{title}}</div>',
            'is_system' => false,
            'is_active' => true,
            'order' => 0,
        ];
    }
}
