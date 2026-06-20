<?php

namespace Database\Seeders;

use App\Models\CardTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the system card-template library with three starter designs:
 * Classic (image-top), Wide (image-left), and Overlay (text on image).
 *
 * Each card uses {token} placeholders that {@see \App\Services\CardRenderer}
 * substitutes against the loop's source entry. {entry_url} is a virtual
 * token resolved to /{template_slug}/{slug}.
 */
class CardTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            [
                'slug' => 'classic',
                'name' => 'Classic',
                'description' => 'Image on top, title + subtitle + CTA underneath. Works for most entries.',
                'category' => 'Generic',
                'sort_order' => 10,
                'html' => $this->classicHtml(),
            ],
            [
                'slug' => 'wide',
                'name' => 'Wide (image-left)',
                'description' => 'Two-column card with image on the left, content on the right.',
                'category' => 'Generic',
                'sort_order' => 20,
                'html' => $this->wideHtml(),
            ],
            [
                'slug' => 'overlay',
                'name' => 'Overlay',
                'description' => 'Full-image background with text overlaid on a dark gradient. Best with high-contrast imagery.',
                'category' => 'Generic',
                'sort_order' => 30,
                'html' => $this->overlayHtml(),
            ],
        ];

        foreach ($cards as $card) {
            CardTemplate::updateOrCreate(
                ['slug' => $card['slug']],
                array_merge($card, ['is_system' => true, 'source_template_slug' => null]),
            );
        }
    }

    private function classicHtml(): string
    {
        return <<<'HTML'
<article class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow ring-1 ring-black/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
  <a href="{entry_url}" class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
    <img src="{main_image:preview|/themes/kretaeiendom/images/home/house-7.jpg}" alt="{title}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
  </a>
  <div class="flex flex-1 flex-col p-5">
    <h3 class="line-clamp-1 text-xl font-bold capitalize transition-colors group-hover:text-blue-600">
      <a href="{entry_url}">{title}</a>
    </h3>
    <p class="mt-1 line-clamp-2 text-sm text-slate-500">{description}</p>
    <a href="{entry_url}" class="mt-auto pt-5 inline-flex items-center gap-1.5 text-sm font-semibold transition-colors hover:text-blue-600">
      View
      <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
  </div>
</article>
HTML;
    }

    private function wideHtml(): string
    {
        return <<<'HTML'
<article class="group flex overflow-hidden rounded-2xl bg-white shadow ring-1 ring-black/5 transition-all duration-300 hover:shadow-lg">
  <a href="{entry_url}" class="relative block aspect-square w-2/5 overflow-hidden bg-slate-100">
    <img src="{main_image:preview|/themes/kretaeiendom/images/home/house-7.jpg}" alt="{title}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
  </a>
  <div class="flex flex-1 flex-col p-6">
    <h3 class="line-clamp-1 text-xl font-bold capitalize group-hover:text-blue-600">
      <a href="{entry_url}">{title}</a>
    </h3>
    <p class="mt-2 line-clamp-3 text-sm text-slate-500">{description}</p>
    <a href="{entry_url}" class="mt-auto inline-flex items-center gap-1.5 text-sm font-semibold hover:text-blue-600">
      Read more
      <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
  </div>
</article>
HTML;
    }

    private function overlayHtml(): string
    {
        return <<<'HTML'
<a href="{entry_url}" class="group relative block aspect-[4/5] overflow-hidden rounded-2xl bg-slate-900">
  <img src="{main_image:preview|/themes/kretaeiendom/images/home/house-7.jpg}" alt="{title}" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover opacity-80 transition-all duration-500 group-hover:scale-105 group-hover:opacity-100">
  <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
  <div class="absolute inset-x-0 bottom-0 p-6 text-white">
    <h3 class="text-2xl font-extrabold capitalize drop-shadow">{title}</h3>
    <p class="mt-1 text-sm text-white/80 line-clamp-2">{description}</p>
  </div>
</a>
HTML;
    }
}
