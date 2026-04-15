<?php

namespace Tests\Feature;

use App\Models\ContentNode;
use App\Models\PageSection;
use App\Models\SectionTemplate;
use App\Models\Template;
use App\Services\PageImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageImporterTest extends TestCase
{
    use RefreshDatabase;

    private PageImporter $importer;

    private Template $template;

    private SectionTemplate $sectionTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importer = app(PageImporter::class);

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

        $this->sectionTemplate = SectionTemplate::firstOrCreate(
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
    }

    private function makeNode(int $contentId = 999): ContentNode
    {
        static $counter = 0;
        $counter++;

        return ContentNode::create([
            'template_id' => $this->template->id,
            'content_type' => 'App\\Models\\Home',
            'content_id' => $contentId,
            'title' => 'Test Page '.$counter,
            'slug' => 'test-page-'.$counter,
            'url_path' => '/test-page-'.$counter,
            'level' => 0,
            'tree_path' => '/'.$counter,
            'is_published' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_import_creates_new_sections(): void
    {
        $node = $this->makeNode(991);

        $this->assertEquals(0, PageSection::where('sectionable_id', 991)->count());

        $this->importer->import($node, [
            'sections' => [
                [
                    'template' => 'hero-simple',
                    'name' => 'Main Hero',
                    'order' => 1,
                    'active' => true,
                    'content' => ['title' => 'Welcome'],
                    'settings' => [],
                ],
            ],
        ]);

        $this->assertEquals(1, PageSection::where('sectionable_id', 991)->count());

        $section = PageSection::where('sectionable_id', 991)->first();
        $this->assertEquals('Main Hero', $section->name);
        $this->assertEquals(['title' => 'Welcome'], $section->content);
    }

    public function test_import_updates_existing_sections(): void
    {
        $node = $this->makeNode(992);

        $section = PageSection::create([
            'sectionable_type' => 'App\\Models\\Home',
            'sectionable_id' => 992,
            'section_template_id' => $this->sectionTemplate->id,
            'section_type' => 'hero-simple',
            'name' => 'Old Name',
            'order' => 1,
            'is_active' => true,
            'content' => ['title' => 'Old Title'],
        ]);

        $this->importer->import($node, [
            'sections' => [
                [
                    'id' => $section->id,
                    'template' => 'hero-simple',
                    'name' => 'New Name',
                    'order' => 1,
                    'active' => true,
                    'content' => ['title' => 'New Title'],
                    'settings' => [],
                ],
            ],
        ]);

        $section->refresh();
        $this->assertEquals('New Name', $section->name);
        $this->assertEquals(['title' => 'New Title'], $section->content);
    }

    public function test_import_deletes_removed_sections(): void
    {
        $node = $this->makeNode(993);

        for ($i = 1; $i <= 3; $i++) {
            PageSection::create([
                'sectionable_type' => 'App\\Models\\Home',
                'sectionable_id' => 993,
                'section_template_id' => $this->sectionTemplate->id,
                'section_type' => 'hero-simple',
                'name' => 'Section '.$i,
                'order' => $i,
                'is_active' => true,
                'content' => [],
            ]);
        }

        $this->assertEquals(3, PageSection::where('sectionable_id', 993)->count());

        $this->importer->import($node, ['sections' => []]);

        $this->assertEquals(0, PageSection::where('sectionable_id', 993)->count());
    }

    public function test_import_syncs_page_layout_on_content_node(): void
    {
        $node = $this->makeNode(994);

        $this->assertNull($node->page_layout);

        $this->importer->import($node, [
            'sections' => [
                [
                    'template' => 'hero-simple',
                    'name' => 'Hero',
                    'order' => 1,
                    'active' => true,
                    'content' => [],
                    'settings' => [],
                ],
            ],
        ]);

        $node->refresh();
        $this->assertNotNull($node->page_layout);
        $this->assertArrayHasKey('sections', $node->page_layout);
        $this->assertCount(1, $node->page_layout['sections']);
    }
}
