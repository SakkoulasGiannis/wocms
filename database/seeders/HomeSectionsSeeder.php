<?php

namespace Database\Seeders;

use App\Models\Home;
use App\Models\PageSection;
use App\Models\SectionTemplate;
use Illuminate\Database\Seeder;

class HomeSectionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create SectionTemplate records for new home sections
        $templates = [
            [
                'name' => 'Hero Slider Home 5',
                'slug' => 'hero-slider-home5',
                'category' => 'hero',
                'description' => 'Full-screen swiper slider with animated text overlay and thumbnail pagination',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Property Search',
                'slug' => 'property-search',
                'category' => 'content',
                'description' => 'Property search/filter form with tabs, price range, amenities',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Services Grid',
                'slug' => 'services-grid',
                'category' => 'features',
                'description' => '3-column services grid (Buy/Sell/Rent)',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Explore Cities',
                'slug' => 'explore-cities',
                'category' => 'content',
                'description' => 'Location/city carousel cards',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Featured Property',
                'slug' => 'featured-property',
                'category' => 'content',
                'description' => 'Image with featured property card',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Testimonials Carousel',
                'slug' => 'testimonials-carousel',
                'category' => 'content',
                'description' => 'Testimonials swiper carousel',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Benefits',
                'slug' => 'benefits',
                'category' => 'features',
                'description' => 'Why Choose Us section with image and benefit items',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Agents Grid',
                'slug' => 'agents-grid',
                'category' => 'team',
                'description' => 'Team members carousel',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Latest Blog Posts',
                'slug' => 'latest-blog-posts',
                'category' => 'blog',
                'description' => 'Blog posts grid + carousel (loads real posts from DB)',
                'is_system' => false,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            SectionTemplate::updateOrCreate(
                ['slug' => $tpl['slug']],
                array_merge($tpl, ['html_template' => ''])
            );
        }

        // 2. Switch Home to sections render mode
        $home = Home::find(1);
        if (! $home) {
            $this->command->warn('Home record not found (id=1). Skipping section creation.');

            return;
        }

        $home->update(['render_mode' => 'sections']);

        // 3. Remove existing sections for Home (clean slate)
        PageSection::where('sectionable_type', Home::class)
            ->where('sectionable_id', $home->id)
            ->delete();

        // 4. Create PageSections with default content in order
        $sections = [
            [
                'slug' => 'hero-slider-home5',
                'name' => 'Hero Slider',
                'content' => [
                    'heading' => 'Indulge in Your',
                    'animated_words' => ['Sanctuary', 'Safe House'],
                    'subtitle' => 'Discover your private oasis, where every corner, from the spacious garden to the relaxing pool, is crafted for your comfort and enjoyment.',
                    'categories' => [
                        ['icon' => 'icon-house-fill', 'label' => 'Houses', 'url' => '#'],
                        ['icon' => 'icon-villa-fill', 'label' => 'Villa', 'url' => '#'],
                        ['icon' => 'icon-office-fill', 'label' => 'Office', 'url' => '#'],
                        ['icon' => 'icon-apartment', 'label' => 'Apartments', 'url' => '#'],
                    ],
                    'bg_slides' => [
                        '/themes/kretaeiendom/images/slider/slider-5.jpg',
                        '/themes/kretaeiendom/images/slider/slider-5-1.jpg',
                        '/themes/kretaeiendom/images/slider/slider-5-2.jpg',
                        '/themes/kretaeiendom/images/slider/slider-5-3.jpg',
                    ],
                    'thumb_slides' => [
                        '/themes/kretaeiendom/images/slider/slider-pagi.jpg',
                        '/themes/kretaeiendom/images/slider/slider-pagi2.jpg',
                        '/themes/kretaeiendom/images/slider/slider-pagi3.jpg',
                        '/themes/kretaeiendom/images/slider/slider-pagi4.jpg',
                    ],
                ],
            ],
            [
                'slug' => 'property-search',
                'name' => 'Property Search',
                'content' => [],
            ],
            [
                'slug' => 'services-grid',
                'name' => 'Our Services',
                'content' => [
                    'subtitle' => 'Our Services',
                    'title' => 'Welcome to Kreta Eiendom',
                    'items' => [
                        ['image' => '/themes/kretaeiendom/images/service/home-1.png', 'title' => 'Buy A New Home', 'description' => 'Discover your dream home effortlessly. Explore diverse properties and expert guidance for a seamless buying experience.', 'link' => '#'],
                        ['image' => '/themes/kretaeiendom/images/service/home-2.png', 'title' => 'Sell a Home', 'description' => 'Sell confidently with expert guidance and effective strategies, showcasing your property\'s best features for a successful sale.', 'link' => '#'],
                        ['image' => '/themes/kretaeiendom/images/service/home-3.png', 'title' => 'Rent a Home', 'description' => 'Discover your perfect rental effortlessly. Explore a diverse variety of listings tailored precisely to suit your unique lifestyle needs.', 'link' => '#'],
                    ],
                ],
            ],
            [
                'slug' => 'explore-cities',
                'name' => 'Explore Cities',
                'content' => [
                    'subtitle' => 'Explore Cities',
                    'title' => 'Our Location For You',
                ],
            ],
            [
                'slug' => 'featured-property',
                'name' => 'Featured Property',
                'content' => [],
            ],
            [
                'slug' => 'testimonials-carousel',
                'name' => 'Testimonials',
                'content' => [
                    'subtitle' => 'Our Testimonials',
                    'title' => 'What\'s people say\'s',
                ],
            ],
            [
                'slug' => 'benefits',
                'name' => 'Why Choose Us',
                'content' => [
                    'subtitle' => 'Our Benefit',
                    'title' => 'Why Choose Kreta Eiendom',
                    'description' => 'Our seasoned team excels in real estate with years of successful market navigation, offering informed decisions and optimal results.',
                    'image' => '/themes/kretaeiendom/images/banner/img-w-text5.jpg',
                ],
            ],
            [
                'slug' => 'agents-grid',
                'name' => 'Meet Our Agents',
                'content' => [
                    'subtitle' => 'Our Teams',
                    'title' => 'Meet Our Agents',
                ],
            ],
            [
                'slug' => 'latest-blog-posts',
                'name' => 'Latest Blog Posts',
                'content' => [
                    'subtitle' => 'Recent Articles',
                    'title' => 'Latest News',
                ],
            ],
        ];

        foreach ($sections as $order => $sectionData) {
            $template = SectionTemplate::where('slug', $sectionData['slug'])->first();

            $home->sections()->create([
                'section_template_id' => $template?->id,
                'section_type' => str_replace('-', '_', $sectionData['slug']),
                'name' => $sectionData['name'],
                'content' => $sectionData['content'],
                'settings' => $sectionData['settings'] ?? [],
                'order' => $order + 1,
                'is_active' => true,
            ]);
        }

        $this->command->info('Home page sections created successfully! ('.count($sections).' sections)');
    }
}
