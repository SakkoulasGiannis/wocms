<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\PageBuilder\Livewire\Admin\PageSections\VisualPageEditor;
use Modules\PageBuilder\Models\PageSection;
use Modules\PageBuilder\Models\SectionTemplate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Covers the DRAFT model of the VisualPageEditor: every mutating action (add,
 * edit, move, reorder, delete, duplicate, toggle) must mutate the in-memory
 * draft ONLY — nothing is persisted to `page_sections` until the single Save
 * button (saveDraft) reconciles the draft to the database in one transaction.
 */
class VisualPageEditorDraftTest extends TestCase
{
    use RefreshDatabase;

    protected string $sectionableType = 'App\\Models\\Template';

    protected int $sectionableId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->assignRole($adminRole);
        $this->actingAs($user);

        // The editor is polymorphic; we attach sections to a Template entity
        // (its table exists in the test schema). No ?scope param is passed, so
        // the component stays in legacy per-entity mode (scope = null).
        $sectionable = Template::create([
            'name' => 'Designable',
            'slug' => 'designable',
            'render_mode' => 'sections',
            'is_active' => true,
        ]);
        $this->sectionableId = $sectionable->id;
    }

    private function makeTemplate(string $name = 'Hero'): SectionTemplate
    {
        return SectionTemplate::factory()->create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
        ]);
    }

    private function makeSection(array $overrides = []): PageSection
    {
        return PageSection::factory()->create(array_merge([
            'sectionable_type' => $this->sectionableType,
            'sectionable_id' => $this->sectionableId,
            'scope' => null,
            'section_type' => 'primitive_div',
            'order' => 1,
            'parent_section_id' => null,
        ], $overrides));
    }

    private function editor()
    {
        return Livewire::test(VisualPageEditor::class, [
            'sectionableType' => str_replace('\\', '-', $this->sectionableType),
            'sectionableId' => $this->sectionableId,
        ]);
    }

    // ─── Add ─────────────────────────────────────────────────────────────────

    public function test_adding_a_section_does_not_persist_until_save(): void
    {
        $template = $this->makeTemplate('Hero');

        $component = $this->editor()
            ->call('openAddPanel')
            ->call('selectTemplate', $template->id)
            ->set('sectionName', 'My New Hero')
            ->call('saveSection');

        // Draft has the section…
        $this->assertCount(1, $component->get('sections'));
        $this->assertTrue($component->get('isDirty'));

        // …but nothing is in the database yet.
        $this->assertDatabaseMissing('page_sections', ['name' => 'My New Hero']);

        $component->call('saveDraft');

        $this->assertDatabaseHas('page_sections', [
            'name' => 'My New Hero',
            'sectionable_type' => $this->sectionableType,
            'sectionable_id' => $this->sectionableId,
            'parent_section_id' => null,
            'order' => 1,
        ]);
        $this->assertFalse($component->get('isDirty'));
    }

    public function test_new_draft_section_has_negative_temp_id_until_save(): void
    {
        $template = $this->makeTemplate();

        $component = $this->editor()
            ->call('openAddPanel')
            ->call('selectTemplate', $template->id)
            ->call('saveSection');

        $sections = $component->get('sections');
        $this->assertLessThan(0, (int) $sections[0]['id']);

        $component->call('saveDraft');

        // After save the reloaded draft carries the real (positive) id.
        $reloaded = $component->get('sections');
        $this->assertGreaterThan(0, (int) $reloaded[0]['id']);
    }

    // ─── Edit ────────────────────────────────────────────────────────────────

    public function test_editing_name_and_content_does_not_persist_until_save(): void
    {
        $template = $this->makeTemplate();
        $section = $this->makeSection([
            'name' => 'Original',
            'section_template_id' => $template->id,
            'content' => ['heading' => 'old'],
        ]);

        $component = $this->editor()
            ->call('selectSection', $section->id)
            ->set('sectionName', 'Renamed')
            ->set('sectionContent.heading', 'new heading');

        // DB row is untouched.
        $this->assertDatabaseHas('page_sections', ['id' => $section->id, 'name' => 'Original']);
        $this->assertSame('Original', $section->fresh()->name);
        $this->assertSame('old', $section->fresh()->content['heading']);
        $this->assertTrue($component->get('isDirty'));

        $component->call('saveDraft');

        $fresh = $section->fresh();
        $this->assertSame('Renamed', $fresh->name);
        $this->assertSame('new heading', $fresh->content['heading']);
    }

    public function test_save_content_folds_editor_patch_into_draft_only(): void
    {
        $template = $this->makeTemplate();
        $section = $this->makeSection([
            'section_template_id' => $template->id,
            'content' => ['body' => 'before'],
        ]);

        $component = $this->editor()
            ->call('selectSection', $section->id)
            ->call('saveContent', ['body' => 'after']);

        // Still only in the draft.
        $this->assertSame('before', $section->fresh()->content['body']);

        $component->call('saveDraft');
        $this->assertSame('after', $section->fresh()->content['body']);
    }

    // ─── Move / reorder ──────────────────────────────────────────────────────

    public function test_move_changes_parent_and_order_only_after_save(): void
    {
        $container = $this->makeSection(['name' => 'Container', 'order' => 1]);
        $child = $this->makeSection([
            'name' => 'Child',
            'order' => 2,
            'section_type' => 'wysiwyg',
        ]);

        $component = $this->editor()
            ->call('moveSection', $child->id, $container->id, 1);

        // Not yet persisted.
        $this->assertNull($child->fresh()->parent_section_id);
        $this->assertTrue($component->get('isDirty'));

        $component->call('saveDraft');

        $fresh = $child->fresh();
        $this->assertSame($container->id, $fresh->parent_section_id);
        $this->assertSame(1, (int) $fresh->order);
    }

    public function test_reorder_is_not_persisted_until_save(): void
    {
        $a = $this->makeSection(['name' => 'A', 'order' => 1, 'section_type' => 'wysiwyg']);
        $b = $this->makeSection(['name' => 'B', 'order' => 2, 'section_type' => 'wysiwyg']);

        $component = $this->editor()
            ->call('reorderSections', [$b->id, $a->id]);

        // DB order unchanged before save.
        $this->assertSame(1, (int) $a->fresh()->order);
        $this->assertSame(2, (int) $b->fresh()->order);

        $component->call('saveDraft');

        $this->assertSame(1, (int) $b->fresh()->order);
        $this->assertSame(2, (int) $a->fresh()->order);
    }

    // ─── Delete ──────────────────────────────────────────────────────────────

    public function test_delete_removes_row_only_after_save(): void
    {
        $section = $this->makeSection(['name' => 'Doomed', 'section_type' => 'wysiwyg']);

        $component = $this->editor()
            ->call('deleteSection', $section->id);

        // Still in the DB, gone from the draft.
        $this->assertDatabaseHas('page_sections', ['id' => $section->id, 'deleted_at' => null]);
        $this->assertCount(0, $component->get('sections'));

        $component->call('saveDraft');

        $this->assertSoftDeleted('page_sections', ['id' => $section->id]);
    }

    public function test_delete_removes_subtree_after_save(): void
    {
        $parent = $this->makeSection(['name' => 'Parent', 'order' => 1]);
        $child = $this->makeSection([
            'name' => 'Child',
            'order' => 1,
            'parent_section_id' => $parent->id,
            'section_type' => 'wysiwyg',
        ]);

        $this->editor()
            ->call('deleteSection', $parent->id)
            ->call('saveDraft');

        $this->assertSoftDeleted('page_sections', ['id' => $parent->id]);
        $this->assertSoftDeleted('page_sections', ['id' => $child->id]);
    }

    // ─── Duplicate ───────────────────────────────────────────────────────────

    public function test_duplicate_is_drafted_then_persisted_on_save(): void
    {
        $section = $this->makeSection(['name' => 'Block', 'order' => 1, 'section_type' => 'wysiwyg']);

        $component = $this->editor()
            ->call('duplicateSection', $section->id);

        // Draft now has 2, DB still has 1.
        $this->assertCount(2, $component->get('sections'));
        $this->assertSame(1, PageSection::count());

        $component->call('saveDraft');

        $this->assertSame(2, PageSection::count());
        $this->assertDatabaseHas('page_sections', ['name' => 'Block (copy)', 'order' => 2]);
    }

    // ─── Toggle ──────────────────────────────────────────────────────────────

    public function test_toggle_visibility_and_active_persist_only_on_save(): void
    {
        $section = $this->makeSection([
            'section_type' => 'wysiwyg',
            'is_active' => true,
            'is_visible' => true,
        ]);

        $component = $this->editor()
            ->call('toggleVisibility', $section->id)
            ->call('toggleActive', $section->id);

        $this->assertTrue((bool) $section->fresh()->is_visible);
        $this->assertTrue((bool) $section->fresh()->is_active);

        $component->call('saveDraft');

        $fresh = $section->fresh();
        $this->assertFalse((bool) $fresh->is_visible);
        $this->assertFalse((bool) $fresh->is_active);
    }

    // ─── Discard / Revert ────────────────────────────────────────────────────

    public function test_discard_restores_original_db_state_without_writing(): void
    {
        $section = $this->makeSection(['name' => 'Keep', 'section_type' => 'wysiwyg']);

        $component = $this->editor()
            ->call('selectSection', $section->id)
            ->set('sectionName', 'Changed')
            ->call('deleteSection', $section->id);

        $this->assertCount(0, $component->get('sections'));

        $component->call('discardDraft');

        // Draft back to the DB state, DB unchanged.
        $this->assertCount(1, $component->get('sections'));
        $this->assertSame('Keep', $section->fresh()->name);
        $this->assertFalse($component->get('isDirty'));
    }

    // ─── Full-tree Save (including a nested child) ───────────────────────────

    public function test_save_persists_full_tree_including_nested_child_in_one_go(): void
    {
        $containerTpl = $this->makeTemplate('Container');
        $childTpl = $this->makeTemplate('Card');

        $component = $this->editor();

        // Add a container section (draft).
        $component->call('openAddPanel')
            ->call('selectTemplate', $containerTpl->id)
            ->set('sectionName', 'Wrapper')
            ->call('saveSection');

        $containerTempId = (int) $component->get('sections')[0]['id'];
        $this->assertLessThan(0, $containerTempId);

        // Add a child UNDER the (still unsaved) container — child references the
        // parent's temp id.
        $component->call('openAddPanelForChild', $containerTempId)
            ->call('selectTemplate', $childTpl->id)
            ->set('sectionName', 'Inner Card')
            ->call('saveSection');

        // Nothing in the DB yet.
        $this->assertSame(0, PageSection::count());

        $component->call('saveDraft');

        // Both rows exist, and the child's parent_section_id resolved to the
        // container's REAL auto-increment id.
        $this->assertSame(2, PageSection::count());

        $wrapper = PageSection::where('name', 'Wrapper')->firstOrFail();
        $card = PageSection::where('name', 'Inner Card')->firstOrFail();

        $this->assertNull($wrapper->parent_section_id);
        $this->assertSame($wrapper->id, $card->parent_section_id);
        $this->assertSame(1, (int) $card->order);
    }

    public function test_save_with_no_changes_is_a_clean_noop(): void
    {
        $section = $this->makeSection(['name' => 'Stable', 'section_type' => 'wysiwyg']);

        $this->editor()
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertSame(1, PageSection::count());
        $this->assertSame('Stable', $section->fresh()->name);
    }
}
