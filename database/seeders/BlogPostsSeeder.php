<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Faker\Generator;

class BlogPostsSeeder extends Seeder
{
    protected $count = 10;

    /**
     * Set the count of blog posts to generate
     */
    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    /**
     * Run the database seeds.
     *
     * Usage:
     *   php artisan db:seed --class=BlogPostsSeeder
     *   php artisan db:seed --class=BlogPostsSeeder --count=50
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get count from property (set by command) or command line option, default to 10
        $count = $this->count;

        $this->command->info("🚀 Creating {$count} blog posts...");

        // Get the Blog template
        $blogTemplate = \App\Models\Template::where('slug', 'blog')->first();
        if (!$blogTemplate) {
            $this->command->error('Blog template not found. Please run BlogTemplateSeeder first.');
            return;
        }

        // Array of tech/CMS related topics for more relevant content
        $topics = [
            'Web Development', 'Laravel', 'PHP', 'JavaScript', 'React', 'Vue.js',
            'Content Management', 'SEO', 'UI/UX Design', 'DevOps', 'API Development',
            'Database Design', 'Cloud Computing', 'Cybersecurity', 'Mobile Development',
            'TypeScript', 'Tailwind CSS', 'Git', 'Docker', 'Microservices'
        ];

        $authors = [
            'John Smith', 'Jane Doe', 'Alex Johnson', 'Maria Garcia',
            'David Chen', 'Sarah Williams', 'Michael Brown', 'Emily Davis',
            'Robert Taylor', 'Lisa Anderson'
        ];

        for ($i = 0; $i < $count; $i++) {
            $title = $this->generateTitle($faker, $topics);
            $slug = Str::slug($title);

            // Ensure unique slug
            $originalSlug = $slug;
            $counter = 1;
            while (\DB::table('blogs')->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Generate Tailwind-styled HTML content
            $body = $this->generateBlogContent($faker, $title);

            // Random tags
            $tagsList = $faker->randomElements($topics, $faker->numberBetween(2, 5));
            $tags = implode(', ', $tagsList);

            // Random published date in the last 6 months
            $publishedAt = $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d H:i:s');

            $blogId = \DB::table('blogs')->insertGetId([
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $faker->paragraph(2),
                'body' => $body,
                'body_css' => $this->generateCustomCSS(),
                'featured_image' => null, // Can be populated later with real images
                'author' => $faker->randomElement($authors),
                'tags' => $tags,
                'published_at' => $publishedAt,
                'render_mode' => 'full_page_grapejs',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create ContentNode for this blog post
            \App\Models\ContentNode::create([
                'template_id' => $blogTemplate->id,
                'content_type' => 'App\\Models\\Blog',
                'content_id' => $blogId,
                'title' => $title,
                'slug' => $slug,
                'url_path' => '/blog/' . $slug,
                'is_published' => true,
                'level' => 1,
                'tree_path' => '/' . ($i + 1), // Simple incremental tree path
            ]);

            $this->command->info("  ✓ Created: {$title}");
        }

        $this->command->info("✅ Successfully created {$count} blog posts with ContentNodes!");
    }

    /**
     * Generate a realistic blog post title
     */
    protected function generateTitle(Generator $faker, array $topics): string
    {
        $templates = [
            'Getting Started with %s: A Complete Guide',
            'Top 10 %s Best Practices for 2024',
            'How to Master %s in 30 Days',
            'The Ultimate %s Tutorial for Beginners',
            'Advanced %s Techniques You Need to Know',
            '%s: Everything You Need to Know',
            'Building Modern Applications with %s',
            'Why %s is Essential for Modern Development',
            'A Deep Dive into %s',
            'Mastering %s: Tips and Tricks',
            'The Future of %s in Web Development',
            'Understanding %s: A Comprehensive Guide',
        ];

        $template = $faker->randomElement($templates);
        $topic = $faker->randomElement($topics);

        return sprintf($template, $topic);
    }

    /**
     * Generate realistic blog content with Tailwind CSS
     */
    protected function generateBlogContent(Generator $faker, string $title): string
    {
        $paragraphs = $faker->numberBetween(4, 8);

        $content = '<div class="max-w-4xl mx-auto px-4 py-8">';

        // Hero section
        $content .= '
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">' . $title . '</h1>
                <div class="flex items-center text-gray-600 text-sm space-x-4">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        ' . $faker->name() . '
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                        ' . $faker->dateTimeBetween('-2 months', 'now')->format('F j, Y') . '
                    </span>
                </div>
            </div>
        ';

        // Intro paragraph with highlight
        $content .= '
            <div class="prose prose-lg mb-6">
                <p class="text-xl text-gray-700 leading-relaxed">' . $faker->paragraph(4) . '</p>
            </div>
        ';

        // Main content paragraphs with occasional special sections
        for ($i = 0; $i < $paragraphs; $i++) {
            // Regular paragraph
            $content .= '<p class="mb-6 text-gray-700 leading-relaxed">' . $faker->paragraph($faker->numberBetween(4, 7)) . '</p>';

            // Add special sections occasionally
            if ($i === 2) {
                // Code snippet section
                $content .= '
                    <div class="bg-gray-900 rounded-lg p-6 mb-6 overflow-x-auto">
                        <pre class="text-green-400 text-sm"><code>' . $this->generateCodeSnippet($faker) . '</code></pre>
                    </div>
                ';
            }

            if ($i === 4 && $paragraphs > 5) {
                // Quote/callout section
                $content .= '
                    <blockquote class="border-l-4 border-blue-500 pl-6 py-4 mb-6 bg-blue-50 rounded-r-lg">
                        <p class="text-gray-800 italic text-lg">' . $faker->sentence(15) . '</p>
                    </blockquote>
                ';
            }

            if ($i === ($paragraphs - 2)) {
                // Bullet points
                $content .= '
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Key Takeaways</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">' . $faker->sentence() . '</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">' . $faker->sentence() . '</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">' . $faker->sentence() . '</span>
                            </li>
                        </ul>
                    </div>
                ';
            }
        }

        // Conclusion
        $content .= '
            <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg border border-blue-100">
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Conclusion</h3>
                <p class="text-gray-700 leading-relaxed">' . $faker->paragraph(3) . '</p>
            </div>
        ';

        $content .= '</div>';

        return $content;
    }

    /**
     * Generate a simple code snippet
     */
    protected function generateCodeSnippet(Generator $faker): string
    {
        $snippets = [
            "<?php\n\nnamespace App\\Http\\Controllers;\n\nuse Illuminate\\Http\\Request;\n\nclass BlogController extends Controller\n{\n    public function index()\n    {\n        return view('blog.index');\n    }\n}",
            "const posts = await fetch('/api/posts')\n    .then(response => response.json())\n    .then(data => data.posts);\n\nconsole.log(posts);",
            "import { useState, useEffect } from 'react';\n\nfunction BlogList() {\n    const [posts, setPosts] = useState([]);\n    \n    useEffect(() => {\n        fetchPosts();\n    }, []);\n    \n    return <div>{posts.map(post => ...)}</div>;\n}",
        ];

        return $faker->randomElement($snippets);
    }

    /**
     * Generate custom CSS for the blog post
     */
    protected function generateCustomCSS(): string
    {
        return "/* Custom styles for this blog post */\n.prose {\n    max-width: 65ch;\n}\n\n.prose h2 {\n    margin-top: 2em;\n    margin-bottom: 1em;\n}";
    }
}
