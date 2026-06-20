<?php

namespace App\Livewire\Admin\AiPageBuilder;

use App\Models\EntityRevision;
use App\Models\PageRevision;
use App\Services\EntityFieldsAgent;
use App\Services\EntityFieldsCompiler;
use App\Services\PageBuilderAgent;
use App\Services\PageCompiler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Embeddable AI action button — drops into any admin screen and gives a
 * context-aware "ask AI to build / edit this" prompt.
 *
 * Usage in a parent Blade view:
 *
 *   <livewire:admin.ai-page-builder.ai-action-button mode="create" model-class="Page" />
 *   <livewire:admin.ai-page-builder.ai-action-button mode="edit"   model-class="Page" :entity-id="$entryId" />
 *
 * Only Page-model entities are wired today. For other entity types the button
 * renders a "coming soon" tooltip instead of nothing — keeps UX consistent.
 */
class AiActionButton extends Component
{
    public string $mode = 'create';          // create | edit

    public string $modelClass = '';          // 'Page' (short) or full FQN

    public string $templateSlug = '';        // required for non-Page entities

    public ?int $entityId = null;            // required for edit mode

    public string $entityLabel = '';         // optional label shown in modal title

    public bool $open = false;               // modal visibility

    public string $prompt = '';

    public ?array $result = null;

    public bool $busy = false;

    public function mount(string $mode = 'create', string $modelClass = '', string $templateSlug = '', ?int $entityId = null, string $entityLabel = ''): void
    {
        $this->mode = in_array($mode, ['create', 'edit'], true) ? $mode : 'create';
        $this->modelClass = $modelClass;
        $this->templateSlug = $templateSlug;
        $this->entityId = $entityId;
        $this->entityLabel = $entityLabel;
    }

    public function isPageModel(): bool
    {
        return in_array(ltrim($this->modelClass, '\\'), ['Page', 'App\\Models\\Page'], true);
    }

    /** Every model with a template now has a builder. Page uses the rich
     *  section/EditorJS pipeline (no templateSlug needed); everything else
     *  uses the generic field-filler which needs the template schema. */
    public function isSupported(): bool
    {
        if ($this->modelClass === '') {
            return false;
        }
        if ($this->isPageModel()) {
            return true;
        }

        return $this->templateSlug !== '';
    }

    public function openModal(): void
    {
        $this->open = true;
        $this->result = null;
        $this->prompt = '';
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    public function run(PageBuilderAgent $pageAgent, EntityFieldsAgent $entityAgent): void
    {
        if (! $this->isSupported()) {
            $this->result = ['ok' => false, 'error' => 'AI builder not wired for this entity type yet.'];

            return;
        }

        $this->validate([
            'prompt' => 'required|string|min:5',
        ], [
            'prompt.required' => 'Tell AI what you want it to do.',
            'prompt.min' => 'A few more words — at least 5 characters.',
        ]);

        if ($this->mode === 'edit' && ! $this->entityId) {
            $this->result = ['ok' => false, 'error' => 'Edit mode requires an entity id.'];

            return;
        }

        $this->busy = true;
        $this->result = null;

        try {
            $this->result = $this->isPageModel()
                ? ($this->mode === 'edit'
                    ? $pageAgent->editPage(pageIdOrSlug: $this->entityId, userPrompt: $this->prompt)
                    : $pageAgent->createPage(userPrompt: $this->prompt, templateSlugs: []))
                : ($this->mode === 'edit'
                    ? $entityAgent->editEntity(templateSlug: $this->templateSlug, entityId: $this->entityId, userPrompt: $this->prompt)
                    : $entityAgent->createEntity(templateSlug: $this->templateSlug, userPrompt: $this->prompt));
        } catch (\Throwable $e) {
            Log::warning('AiActionButton run failed', ['error' => $e->getMessage()]);
            $this->result = ['ok' => false, 'error' => $e->getMessage()];
        } finally {
            $this->busy = false;
        }
    }

    /**
     * Once the result is success, navigate the user somewhere useful. The
     * right destination depends on WHERE the AI button was triggered from:
     *
     *  • From the entries list (/admin/page) → open the entry edit form
     *  • From the entry edit form (/admin/page/{id}/edit) → reload it
     *  • From the visual page editor (/admin/page-sections/visual/...) →
     *    keep the user there and just reload, so the iframe + sections list
     *    pick up the new content. We dispatch an event the JS layer in the
     *    visual editor listens to.
     */
    public function navigateToResult(): void
    {
        if (! $this->result) {
            return;
        }
        $entityId = $this->result['page_id'] ?? $this->result['entity_id'] ?? null;
        if (! $entityId) {
            return;
        }

        // Tell the browser to reload whatever it's currently on. This keeps
        // the user in the visual editor when they triggered the AI from there,
        // and just re-fetches the edit form when they triggered it from there.
        // Falls back to /admin/page/{id}/edit for the entry-list trigger.
        $this->dispatch('ai-edit-complete', entityId: $entityId);
    }

    /**
     * "↶ Undo last AI change" — restores the entity to the pre-revision
     * snapshot the compiler captured right before applying the AI edit.
     * Works for both Page (via PageRevision/PageCompiler) and other entities
     * (via EntityRevision/EntityFieldsCompiler).
     */
    public function revertLast(): void
    {
        $revisionId = $this->result['pre_revision_id'] ?? null;
        if (! $revisionId) {
            $this->result = ['ok' => false, 'error' => 'No pre-change snapshot to restore.'];

            return;
        }

        $this->busy = true;
        try {
            if ($this->isPageModel()) {
                $rev = PageRevision::find($revisionId);
                if (! $rev) {
                    $this->result = ['ok' => false, 'error' => "Pre-change snapshot #{$revisionId} not found."];

                    return;
                }
                $restored = PageCompiler::fromArray($rev->spec)
                    ->withRevisionMeta(
                        source: 'ai-edit',
                        prompt: "Undo of AI edit (restored from revision #{$rev->id})",
                        userId: Auth::id(),
                    )
                    ->compile();
            } else {
                $rev = EntityRevision::find($revisionId);
                if (! $rev) {
                    $this->result = ['ok' => false, 'error' => "Pre-change snapshot #{$revisionId} not found."];

                    return;
                }
                $restored = EntityFieldsCompiler::for($rev->entity_type, $this->templateSlug)
                    ->withFields($rev->fields_json)
                    ->withEntityId($rev->entity_id)
                    ->withRevisionMeta(
                        source: 'ai-edit',
                        prompt: "Undo of AI edit (restored from revision #{$rev->id})",
                        userId: Auth::id(),
                    )
                    ->compile();
            }

            $this->result = $restored + [
                'restored_from' => $rev->id,
                'was_undo' => true,
            ];
        } catch (\Throwable $e) {
            Log::warning('AiActionButton revertLast failed', ['error' => $e->getMessage()]);
            $this->result = ['ok' => false, 'error' => 'Undo failed: '.$e->getMessage()];
        } finally {
            $this->busy = false;
        }
    }

    #[On('ai-action-button:open')]
    public function externalOpen(): void
    {
        $this->openModal();
    }

    public function render()
    {
        return view('livewire.admin.ai-page-builder.ai-action-button');
    }
}
