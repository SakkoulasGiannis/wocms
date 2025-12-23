<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplateField;
use App\Models\ContentNode;
use App\Services\TemplateTableGenerator;
use Illuminate\Database\Seeder;

class BlogExampleSeeder extends Seeder
{
    /**
     * Seed a complete blog example with parent-child templates
     */
    public function run(): void
    {
        $this->command->info('Creating Blog example...');

        // 1. Create Blog Template (parent, no database)
        $blogTemplate = Template::create([
            'name' => 'Blog',
            'slug' => 'blog',
            'description' => 'Blog listing page (container)',
            'is_active' => true,
            'is_system' => false,
            'show_in_menu' => true,
            'menu_label' => 'Blog',
            'menu_icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
            'menu_order' => 10,
            'allow_children' => true,
            'allow_new_pages' => false,
            'requires_database' => false,
            'has_physical_file' => true,
            'file_path' => 'frontend/templates/blog.blade.php',
            'parent_id' => 1,
            'tree_level' => 1,
            'tree_path' => '/1/blog',
        ]);

        // 2. Create Blog Post Template (child, with database)
        $blogPostTemplate = Template::create([
            'name' => 'Blog Post',
            'slug' => 'blog-post',
            'description' => 'Individual blog post',
            'is_active' => true,
            'is_system' => false,
            'show_in_menu' => false,
            'allow_children' => false,
            'allow_new_pages' => true,
            'requires_database' => true,
            'has_physical_file' => true,
            'file_path' => 'frontend/templates/blog-post.blade.php',
            'parent_id' => $blogTemplate->id,
            'tree_level' => 2,
            'tree_path' => '/1/blog/blog-post',
        ]);

        $blogTemplate->update(['allowed_child_templates' => [$blogPostTemplate->id]]);

        // 3. Add fields to Blog Post
        $fields = [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'is_required' => true, 'order' => 0],
            ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'is_required' => true, 'order' => 1],
            ['name' => 'excerpt', 'label' => 'Excerpt', 'type' => 'textarea', 'is_required' => false, 'order' => 2],
            ['name' => 'content', 'label' => 'Content', 'type' => 'wysiwyg', 'is_required' => true, 'order' => 3],
            ['name' => 'featured_image', 'label' => 'Featured Image', 'type' => 'image', 'is_required' => false, 'order' => 4],
        ];

        foreach ($fields as $fieldData) {
            $fieldData['template_id'] = $blogPostTemplate->id;
            TemplateField::create($fieldData);
        }

        // 4. Generate table and model
        $tableGenerator = new TemplateTableGenerator();
        $tableGenerator->createTableAndModel($blogPostTemplate->fresh());

        // 5. Create Blog ContentNode
        $blogNode = ContentNode::create([
            'template_id' => $blogTemplate->id,
            'title' => 'Blog',
            'slug' => 'blog',
            'url_path' => '/blog',
            'level' => 0,
            'tree_path' => '/2',
            'is_published' => true,
        ]);

        // 6. Create sample posts
        $blogPostModel = "App\\Models\\BlogPost";
        $posts = [
            ['title' => 'Welcome to Our Blog', 'slug' => 'welcome', 'excerpt' => 'First post!', 'content' => '<p>Welcome!</p>'],
            ['title' => 'Laravel Tips', 'slug' => 'laravel-tips', 'excerpt' => 'Learn Laravel', 'content' => '<p>Laravel rocks!</p>'],
        ];

        foreach ($posts as $i => $postData) {
            $post = $blogPostModel::create($postData);
            ContentNode::create([
                'template_id' => $blogPostTemplate->id,
                'parent_id' => $blogNode->id,
                'content_type' => $blogPostModel,
                'content_id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'url_path' => '/blog/' . $post->slug,
                'level' => 1,
                'tree_path' => '/2/' . ($i + 1),
                'is_published' => true,
            ]);
        }

        // 7. Create template files
        $this->createTemplateFiles();

        $this->command->info('✓ Blog system created! Visit: http://cms.ddev.site/blog');
    }

    protected function createTemplateFiles()
    {
        $path = resource_path('views/frontend/templates');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        // Blog listing template
        file_put_contents($path . '/blog.blade.php', '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8">{{ $title }}</h1>
        <div class="grid gap-6">
            @foreach($node->children()->where(\'is_published\', true)->get() as $postNode)
                @php $post = $postNode->getContentModel(); @endphp
                @if($post)
                    <article class="bg-white p-6 rounded shadow">
                        <h2 class="text-2xl font-bold mb-2">
                            <a href="{{ $postNode->url_path }}" class="hover:text-blue-600">{{ $postNode->title }}</a>
                        </h2>
                        @if($post->excerpt)
                            <p class="text-gray-600 mb-4">{{ $post->excerpt }}</p>
                        @endif
                        <a href="{{ $postNode->url_path }}" class="text-blue-600">Read more →</a>
                    </article>
                @endif
            @endforeach
        </div>
    </div>
</body>
</html>');

        // Blog post template
        file_put_contents($path . '/blog-post.blade.php', '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <article class="container mx-auto px-4 py-12 max-w-3xl">
        <h1 class="text-5xl font-bold mb-8">{{ $title }}</h1>
        <div class="prose prose-lg max-w-none">
            {!! $content->content !!}
        </div>
        <div class="mt-8">
            @if($node->parent)
                <a href="{{ $node->parent->url_path }}" class="text-blue-600">← Back to {{ $node->parent->title }}</a>
            @endif
        </div>
    </article>
</body>
</html>');
    }
}
