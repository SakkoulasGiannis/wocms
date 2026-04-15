<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentNode>
 */
class ContentNodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(2);

        return [
            'template_id' => 1,
            'parent_id' => null,
            'content_type' => null,
            'content_id' => null,
            'title' => $this->faker->sentence(3),
            'slug' => $slug,
            'url_path' => '/'.$slug,
            'level' => 0,
            'tree_path' => '/1',
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
