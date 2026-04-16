<?php

namespace Modules\PageBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PageBuilder\Models\PageSection;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\PageBuilder\Models\PageSection>
 */
class PageSectionFactory extends Factory
{
    protected $model = PageSection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sectionable_type' => 'App\\Models\\Home',
            'sectionable_id' => 1,
            'section_template_id' => null,
            'section_type' => 'hero-simple',
            'name' => $this->faker->words(2, true),
            'order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'content' => [],
            'settings' => [],
        ];
    }
}
