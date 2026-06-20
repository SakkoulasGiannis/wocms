<?php

namespace App\Livewire\Admin\AiPageBuilder;

use App\Models\Page;
use App\Services\PageBuilderAgent;
use Livewire\Component;
use Modules\PageBuilder\Models\SectionTemplate;

class AiPageBuilderPage extends Component
{
    public string $activeTab = 'create';

    /* ── Create tab state ─────────────────────────────────────────── */
    public string $createPrompt = '';

    /** @var array<string> selected template slugs (empty = all) */
    public array $createTemplates = [];

    /* ── Edit tab state ───────────────────────────────────────────── */
    public string $editPageId = '';

    public string $editPrompt = '';

    /* ── Result panel state ───────────────────────────────────────── */
    public ?array $result = null;

    public bool $busy = false;

    public bool $showJson = false;

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->result = null;
    }

    public function toggleTemplate(string $slug): void
    {
        if (in_array($slug, $this->createTemplates, true)) {
            $this->createTemplates = array_values(array_diff($this->createTemplates, [$slug]));
        } else {
            $this->createTemplates[] = $slug;
        }
    }

    public function runCreate(PageBuilderAgent $agent): void
    {
        $this->validate([
            'createPrompt' => 'required|string|min:5',
        ], [
            'createPrompt.required' => 'Write an instruction for the new page.',
            'createPrompt.min' => 'A few more words — at least 5 characters.',
        ]);

        $this->busy = true;
        $this->result = null;

        try {
            $this->result = $agent->createPage(
                userPrompt: $this->createPrompt,
                templateSlugs: $this->createTemplates,
            );
        } catch (\Throwable $e) {
            $this->result = ['ok' => false, 'error' => $e->getMessage()];
        } finally {
            $this->busy = false;
        }
    }

    public function runEdit(PageBuilderAgent $agent): void
    {
        $this->validate([
            'editPageId' => 'required|string',
            'editPrompt' => 'required|string|min:5',
        ], [
            'editPageId.required' => 'Pick which page you want to change.',
            'editPrompt.required' => 'Write the change you want the AI to make.',
            'editPrompt.min' => 'A few more words — at least 5 characters.',
        ]);

        $this->busy = true;
        $this->result = null;

        try {
            $this->result = $agent->editPage(
                pageIdOrSlug: $this->editPageId,
                userPrompt: $this->editPrompt,
            );
        } catch (\Throwable $e) {
            $this->result = ['ok' => false, 'error' => $e->getMessage()];
        } finally {
            $this->busy = false;
        }
    }

    public function reset_(): void
    {
        $this->result = null;
        $this->createPrompt = '';
        $this->editPrompt = '';
        $this->showJson = false;
    }

    public function render()
    {
        return view('livewire.admin.ai-page-builder.ai-page-builder-page', [
            'allTemplates' => SectionTemplate::query()
                ->where('is_active', true)
                ->orderBy('category')->orderBy('order')
                ->get(['id', 'slug', 'name', 'category']),
            'allPages' => Page::query()
                ->orderBy('title')
                ->get(['id', 'title', 'slug', 'status']),
        ])->layout('layouts.admin-clean');
    }
}
