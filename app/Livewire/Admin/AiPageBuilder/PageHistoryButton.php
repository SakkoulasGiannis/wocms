<?php

namespace App\Livewire\Admin\AiPageBuilder;

use App\Models\EntityRevision;
use App\Models\PageRevision;
use App\Services\EntityFieldsCompiler;
use App\Services\PageCompiler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Drop-in History button for the Page edit form. Shows a badge with the
 * revision count and opens a modal that lists every AI snapshot for the
 * page newest-first, with a Restore button per row.
 *
 *   <livewire:admin.ai-page-builder.page-history-button :page-id="$entryId" />
 */
class PageHistoryButton extends Component
{
    public ?int $pageId = null;            // legacy alias for Page edits

    public string $entityType = '';        // FQN or short — non-Page entities

    public ?int $entityId = null;          // for non-Page entities

    public string $templateSlug = '';      // required for non-Page restore

    public bool $open = false;

    public bool $busy = false;

    public ?array $result = null;

    public function mount(?int $pageId = null, string $entityType = '', ?int $entityId = null, string $templateSlug = ''): void
    {
        $this->pageId = $pageId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->templateSlug = $templateSlug;
    }

    /** True when this instance is tracking a Page (uses page_revisions). */
    public function isPageMode(): bool
    {
        return $this->pageId !== null;
    }

    public function openModal(): void
    {
        $this->open = true;
        $this->result = null;
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    /**
     * Restore a specific revision. Re-runs the compiler with the snapshot's
     * spec — this itself captures a fresh pre-revision so the restore can
     * also be undone.
     */
    public function restore(int $revisionId): void
    {
        $this->busy = true;
        try {
            if ($this->isPageMode()) {
                $rev = PageRevision::find($revisionId);
                if (! $rev || $rev->page_id !== $this->pageId) {
                    $this->result = ['ok' => false, 'error' => "Revision #{$revisionId} not found for this page."];

                    return;
                }
                $this->result = PageCompiler::fromArray($rev->spec)
                    ->withRevisionMeta(
                        source: 'ai-edit',
                        prompt: "Restored from revision #{$rev->id} ({$rev->sourceLabel()})",
                        userId: Auth::id(),
                    )
                    ->compile()
                    + ['restored_from' => $rev->id];
            } else {
                $rev = EntityRevision::find($revisionId);
                if (! $rev || $rev->entity_id !== $this->entityId) {
                    $this->result = ['ok' => false, 'error' => "Revision #{$revisionId} not found for this entity."];

                    return;
                }
                $this->result = EntityFieldsCompiler::for($rev->entity_type, $this->templateSlug)
                    ->withFields($rev->fields_json)
                    ->withEntityId($rev->entity_id)
                    ->withRevisionMeta(
                        source: 'ai-edit',
                        prompt: "Restored from revision #{$rev->id} ({$rev->sourceLabel()})",
                        userId: Auth::id(),
                    )
                    ->compile()
                    + ['restored_from' => $rev->id];
            }
        } catch (\Throwable $e) {
            Log::warning('PageHistoryButton restore failed', ['error' => $e->getMessage()]);
            $this->result = ['ok' => false, 'error' => $e->getMessage()];
        } finally {
            $this->busy = false;
        }
    }

    public function reloadPage(): void
    {
        $slug = $this->isPageMode() ? 'page' : $this->templateSlug;
        $id = $this->isPageMode() ? $this->pageId : $this->entityId;
        $this->redirect(url("/admin/{$slug}/{$id}/edit"), navigate: false);
    }

    public function render()
    {
        if ($this->isPageMode()) {
            $revisions = PageRevision::with('user:id,name')
                ->where('page_id', $this->pageId)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
            $totalCount = PageRevision::where('page_id', $this->pageId)->count();
        } elseif ($this->entityType && $this->entityId) {
            $entityFqn = str_contains($this->entityType, '\\') ? $this->entityType : "App\\Models\\{$this->entityType}";
            $revisions = EntityRevision::with('user:id,name')
                ->where('entity_type', $entityFqn)
                ->where('entity_id', $this->entityId)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
            $totalCount = EntityRevision::where('entity_type', $entityFqn)
                ->where('entity_id', $this->entityId)
                ->count();
        } else {
            $revisions = collect();
            $totalCount = 0;
        }

        return view('livewire.admin.ai-page-builder.page-history-button', compact('revisions', 'totalCount'));
    }
}
