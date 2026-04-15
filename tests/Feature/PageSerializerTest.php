<?php

namespace Tests\Feature;

use App\Models\ContentNode;
use App\Models\PageSection;
use App\Models\SectionTemplate;
use App\Models\Template;
use App\Services\PageSerializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSerializerTest extends TestCase
{
    use RefreshDatabase;

    private PageSerializer $serializer;

    private Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = app(PageSerializer::class);

        $this->template = Template::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'render_mode' => 'sections',
            'is_active' => true,
            'is_public' => true,
            'is_system' => false,
            'show_in_menu' => false,
            'allow_children' => false,
            'allow_new_pages' => true,
            'use_slug_prefix' => false,
            'requires_database' => false,
            'has_physical_file' => false,
            'has_seo' => false,
            'enable_full_page_cache' => false,
        ]);
    }

    public function test_serialize_returns_correct_structure(): void
    {
        $node = ContentNode::create([
            'template_id' => $this->template->id,
            'title' => 'Test Page',
            'slug' => 'test-page',
            'url_path' => '/test-page',
            'level' => 0,
            'tree_path' => '/1',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $layout = $this->serializer->serialize($node);

        $this->assertArrayHasKey('version', $layout);
        $this->assertArrayHasKey('meta', $layout);
        $this->assertArrayHasKey('fields', $layout);
        $this->assertArrayHasKey('sections', $layout);
        $this->assertEquals('1.0', $layout['version']);
        $this->assertEquals('Test Page', $layout['meta']['title']);
        $this->assertEquals('test-page', $layout['meta']['slug']);
    }

    public function test_serialize_includes_sections(): void
    {
        $sectionTemplate = SectionTemplate::firstOrCreate(
            ['slug' => 'hero-simple'],
            [
                'name' => 'Hero Simple',
                'category' => 'hero',
                'html_template' => '<div>{{title}}</div>',
                'is_system' => false,
                'is_active' => true,
                'order' => 0,
            ]
        );

        $node = ContentNode::create([
            'template_id' => $this->template->id,
            'content_type' => 'App\\Models\\Home',
            'content_id' => 999,
            'title' => 'Test Page',
            'slug' => 'test-page-2',
            'url_path' => '/test-page-2',
            'level' => 0,
            'tree_path' => '/2',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        PageSection::create([
            'sectionable_type' => 'App\\Models\\Home',
            'sectionable_id' => 999,
            'section_template_id' => $sectionTemplate->id,
            'section_type' => 'hero-simple',
            'name' => 'Hero One',
            'order' => 1,
            'is_active' => true,
            'content' => [],
        ]);

        PageSection::create([
            'sectionable_type' => 'App\\Models\\Home',
            'sectionable_id' => 999,
            'section_template_id' => $sectionTemplate->id,
            'section_type' => 'hero-simple',
            'name' => 'Hero Two',
            'order' => 2,
            'is_active' => true,
            'content' => [],
        ]);

        $layout = $this->serializer->serialize($node);

        $this->assertCount(2, $layout['sections']);
        $this->assertArrayHasKey('id', $layout['sections'][0]);
        $this->assertArrayHasKey('template', $layout['sections'][0]);
        $this->assertArrayHasKey('content', $layout['sections'][0]);
        $this->assertArrayHasKey('active', $layout['sections'][0]);
    }

    public function test_serialize_section_has_correct_keys(): void
    {
        $sectionTemplate = SectionTemplate::firstOrCreate(
            ['slug' => 'features-grid'],
            [
                'name' => 'Features Grid',
                'category' => 'content',
                'html_template' => '<div>{{title}}</div>',
                'is_system' => false,
                'is_active' => true,
                'order' => 0,
            ]
        );

        $section = PageSection::create([
            'sectionable_type' => 'App\\Models\\Home',
            'sectionable_id' => 999,
            'section_template_id' => $sectionTemplate->id,
            'section_type' => 'features-grid',
            'name' => 'My Features',
            'order' => 1,
            'is_active' => true,
            'content' => ['title' => 'Hello'],
        ]);

        $result = $this->serializer->serializeSection($section);

        $this->assertEquals($section->id, $result['id']);
        $this->assertEquals('features-grid', $result['template']);
        $this->assertEquals('My Features', $result['name']);
        $this->assertEquals(1, $result['order']);
        $this->assertTrue($result['active']);
        $this->assertEquals(['title' => 'Hello'], $result['content']);
    }
}
