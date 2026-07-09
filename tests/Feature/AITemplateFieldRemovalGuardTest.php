<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\TemplateField;
use App\Services\AI\AIContentHandler;
use App\Services\AI\AIManager;
use App\Services\TemplateTableGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * SECURITY REGRESSION: the admin AI chat must NEVER drop a database column or
 * delete a TemplateField when the LLM decides fields should be removed. It may
 * only propose the removal in the returned message; a human must act on it
 * deliberately in the template editor.
 */
class AITemplateFieldRemovalGuardTest extends TestCase
{
    use RefreshDatabase;

    protected Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->template = Template::create([
            'name' => 'Guard Test Template',
            'slug' => 'guard-test-template',
            'model_class' => 'GuardTestTemplate',
            'table_name' => 'guard_test_templates',
            'is_active' => true,
            'is_public' => true,
            'has_seo' => false,
            'render_mode' => 'simple_content',
        ]);

        foreach ([
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'order' => 1],
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'order' => 2],
        ] as $fieldData) {
            TemplateField::create(array_merge($fieldData, ['template_id' => $this->template->id]));
        }

        (new TemplateTableGenerator)->createTableAndModel($this->template);

        $this->assertTrue(Schema::hasTable('guard_test_templates'));
        $this->assertTrue(Schema::hasColumn('guard_test_templates', 'title'));
        $this->assertTrue(Schema::hasColumn('guard_test_templates', 'subtitle'));
    }

    /** @test */
    public function ai_chat_never_drops_a_column_or_deletes_a_template_field_when_asked_to_remove_fields(): void
    {
        // Mock the LLM response exactly as AIManager::modifyTemplate() would return
        // it for an ambiguous message such as "clean up the old fields".
        $mockAiManager = \Mockery::mock(AIManager::class);
        $mockAiManager->shouldReceive('modifyTemplate')
            ->once()
            ->andReturn([
                'action' => 'remove_fields',
                'fields_to_remove' => ['title', 'subtitle'],
                'reason' => 'These fields look unused.',
            ]);

        $handler = new AIContentHandler;
        $this->setProtectedProperty($handler, 'aiManager', $mockAiManager);

        $result = $handler->handleTemplateModification('please clean up the old fields on guard test template');

        $this->assertTrue($result['success']);

        // The message must name the fields it PROPOSES to remove...
        $this->assertStringContainsString('title', $result['message']);
        $this->assertStringContainsString('subtitle', $result['message']);
        // ...and must point the human at the template editor rather than acting itself.
        $this->assertStringContainsString('/admin/templates/'.$this->template->id.'/edit', $result['message']);

        // Nothing was mutated: column still exists...
        $this->assertTrue(Schema::hasColumn('guard_test_templates', 'title'));
        $this->assertTrue(Schema::hasColumn('guard_test_templates', 'subtitle'));

        // ...and the TemplateField rows are untouched.
        $this->assertDatabaseHas('template_fields', [
            'template_id' => $this->template->id,
            'name' => 'title',
        ]);
        $this->assertDatabaseHas('template_fields', [
            'template_id' => $this->template->id,
            'name' => 'subtitle',
        ]);
    }

    /** @test */
    public function protected_columns_are_never_proposed_for_removal_even_if_the_llm_asks(): void
    {
        // 'status' is a protected system column (see TemplateTableGenerator::protectedColumns()).
        // Simulate an LLM response that hallucinates removing it alongside a real field.
        TemplateField::create([
            'template_id' => $this->template->id,
            'name' => 'status',
            'label' => 'Status',
            'type' => 'text',
            'order' => 3,
        ]);

        $mockAiManager = \Mockery::mock(AIManager::class);
        $mockAiManager->shouldReceive('modifyTemplate')
            ->once()
            ->andReturn([
                'action' => 'remove_fields',
                'fields_to_remove' => ['status', 'title'],
            ]);

        $handler = new AIContentHandler;
        $this->setProtectedProperty($handler, 'aiManager', $mockAiManager);

        $result = $handler->handleTemplateModification('remove status and title from guard test template');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('proposed_fields_to_remove', $result);
        $this->assertContains('title', $result['proposed_fields_to_remove']);
        $this->assertNotContains('status', $result['proposed_fields_to_remove']);

        // Still never mutated anything, protected or not.
        $this->assertTrue(Schema::hasColumn('guard_test_templates', 'status'));
        $this->assertDatabaseHas('template_fields', [
            'template_id' => $this->template->id,
            'name' => 'status',
        ]);
    }

    protected function setProtectedProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

    protected function tearDown(): void
    {
        // TemplateTableGenerator::createModel() writes a real model file to
        // app/Models - clean it up so the test suite doesn't leave it behind
        // (same pattern as TemplateEntryCreationTest::tearDown()).
        $modelFile = app_path('Models/GuardTestTemplate.php');

        if (file_exists($modelFile)) {
            unlink($modelFile);
        }

        parent::tearDown();
    }
}
