<?php

namespace Tests\Feature;

use App\Livewire\Admin\TemplateEntries\EntryList;
use App\Models\ContentNode;
use App\Models\Template;
use App\Models\TemplateField;
use App\Models\User;
use App\Services\TemplateTableGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EntryListReorderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $this->user->assignRole($adminRole);
        $this->actingAs($this->user);
    }

    private function makeTemplate(array $overrides = []): Template
    {
        $template = Template::create(array_merge([
            'name' => 'Reorder Test',
            'slug' => 'reorder-test',
            'model_class' => 'ReorderTest',
            'table_name' => 'reorder_tests',
            'is_active' => true,
            'is_public' => true,
            'requires_database' => true,
            'allow_children' => true,
            'render_mode' => 'simple_content',
            'settings' => ['sortable' => true],
        ], $overrides));

        TemplateField::create([
            'template_id' => $template->id,
            'name' => 'title',
            'label' => 'Title',
            'type' => 'text',
            'is_required' => true,
            'is_url_identifier' => true,
            'order' => 1,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'name' => 'slug',
            'label' => 'Slug',
            'type' => 'text',
            'is_required' => true,
            'order' => 2,
        ]);

        (new TemplateTableGenerator)->createTableAndModel($template);

        return $template;
    }

    public function test_reorder_updates_content_node_sort_order_for_tree_templates(): void
    {
        $template = $this->makeTemplate();

        $nodeA = ContentNode::create([
            'template_id' => $template->id,
            'title' => 'A',
            'slug' => 'a',
            'is_published' => true,
            'sort_order' => 0,
        ]);
        $nodeB = ContentNode::create([
            'template_id' => $template->id,
            'title' => 'B',
            'slug' => 'b',
            'is_published' => true,
            'sort_order' => 1,
        ]);
        $nodeC = ContentNode::create([
            'template_id' => $template->id,
            'title' => 'C',
            'slug' => 'c',
            'is_published' => true,
            'sort_order' => 2,
        ]);

        Livewire::test(EntryList::class, ['templateSlug' => $template->slug])
            ->call('reorder', [$nodeC->id, $nodeA->id, $nodeB->id])
            ->assertHasNoErrors();

        $this->assertEquals(0, $nodeC->fresh()->sort_order);
        $this->assertEquals(1, $nodeA->fresh()->sort_order);
        $this->assertEquals(2, $nodeB->fresh()->sort_order);
    }

    public function test_reorder_is_idempotent(): void
    {
        $template = $this->makeTemplate();

        $n1 = ContentNode::create([
            'template_id' => $template->id,
            'title' => '1',
            'slug' => 's1',
            'is_published' => true,
        ]);
        $n2 = ContentNode::create([
            'template_id' => $template->id,
            'title' => '2',
            'slug' => 's2',
            'is_published' => true,
        ]);

        $component = Livewire::test(EntryList::class, ['templateSlug' => $template->slug]);

        $component->call('reorder', [$n2->id, $n1->id]);
        $component->call('reorder', [$n2->id, $n1->id]);

        $this->assertEquals(0, $n2->fresh()->sort_order);
        $this->assertEquals(1, $n1->fresh()->sort_order);
    }

    public function test_reorder_handles_empty_array_without_error(): void
    {
        $template = $this->makeTemplate();

        Livewire::test(EntryList::class, ['templateSlug' => $template->slug])
            ->call('reorder', [])
            ->assertHasNoErrors();
    }

    public function test_reorder_flat_template_falls_back_to_content_node_when_no_sort_order_column(): void
    {
        $template = $this->makeTemplate([
            'slug' => 'reorder-flat',
            'model_class' => 'ReorderFlat',
            'table_name' => 'reorder_flats',
            'allow_children' => false,
        ]);

        $modelClass = "App\\Models\\{$template->model_class}";

        $entry1 = $modelClass::create(['title' => 'E1', 'slug' => 'e1']);
        $entry2 = $modelClass::create(['title' => 'E2', 'slug' => 'e2']);

        $node1 = ContentNode::create([
            'template_id' => $template->id,
            'content_type' => $modelClass,
            'content_id' => $entry1->id,
            'title' => 'E1',
            'slug' => 'e1',
            'is_published' => true,
        ]);
        $node2 = ContentNode::create([
            'template_id' => $template->id,
            'content_type' => $modelClass,
            'content_id' => $entry2->id,
            'title' => 'E2',
            'slug' => 'e2',
            'is_published' => true,
        ]);

        Livewire::test(EntryList::class, ['templateSlug' => $template->slug])
            ->call('reorder', [$entry2->id, $entry1->id])
            ->assertHasNoErrors();

        $this->assertEquals(0, $node2->fresh()->sort_order);
        $this->assertEquals(1, $node1->fresh()->sort_order);
    }
}
