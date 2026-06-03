<?php

namespace App\Livewire\Admin\PageSections;

use App\Models\ContentNode;
use App\Models\PageSection;
use Livewire\Component;

class PageSelector extends Component
{
    public $pages = [];

    public function mount(): void
    {
        $this->loadPages();
    }

    public function loadPages(): void
    {
        // Get distinct sectionable entities from page_sections
        $sectionPages = PageSection::select('sectionable_type', 'sectionable_id')
            ->whereNotNull('sectionable_type')
            ->whereNotNull('sectionable_id')
            ->groupBy('sectionable_type', 'sectionable_id')
            ->get()
            ->map(function ($row) {
                $sectionsCount = PageSection::where('sectionable_type', $row->sectionable_type)
                    ->where('sectionable_id', $row->sectionable_id)
                    ->count();

                $title = $this->getEntityTitle($row->sectionable_type, $row->sectionable_id);
                $shortType = class_basename($row->sectionable_type);

                return [
                    'sectionable_type' => $row->sectionable_type,
                    'sectionable_id' => $row->sectionable_id,
                    'short_type' => $shortType,
                    'title' => $title,
                    'sections_count' => $sectionsCount,
                    'is_home' => $shortType === 'Home',
                ];
            });

        $this->pages = $sectionPages->sortBy(function ($page) {
            return $page['is_home'] ? '0' : '1'.$page['title'];
        })->values()->toArray();
    }

    protected function getEntityTitle(string $type, int $id): string
    {
        // Try to resolve the actual model
        if (class_exists($type)) {
            $model = $type::find($id);
            if ($model) {
                return $model->title ?? $model->name ?? class_basename($type).' #'.$id;
            }
        }

        // Fallback: check ContentNode
        $node = ContentNode::where('content_type', $type)
            ->where('content_id', $id)
            ->first();

        if ($node) {
            return $node->title;
        }

        return class_basename($type).' #'.$id;
    }

    /**
     * Deep-clone a Page (the entity itself + every PageSection that targets it,
     * preserving the parent_section_id tree). Only runs for actual Page rows
     * (Home and other singletons are skipped — duplicating them would create
     * an invalid second copy of a singleton).
     */
    public function duplicatePage(string $type, int $id): void
    {
        if (class_basename($type) !== 'Page' || ! class_exists($type)) {
            session()->flash('error', 'Μόνο σελίδες (Pages) μπορούν να αντιγραφούν.');

            return;
        }

        $original = $type::find($id);
        if (! $original) {
            return;
        }

        $newPage = null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($type, $id, $original, &$newPage) {
            // 1) Clone the Page row with a unique slug.
            $newPage = $original->replicate();
            $newPage->title = ($original->title ?? 'Untitled').' (αντίγραφο)';

            $baseSlug = $original->slug ?: \Illuminate\Support\Str::slug($newPage->title);
            $slug = $baseSlug.'-copy';
            $i = 2;
            while ($type::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-copy-'.$i++;
            }
            $newPage->slug = $slug;

            // Safer defaults so the clone doesn't auto-publish over the original.
            if (\Illuminate\Support\Facades\Schema::hasColumn($newPage->getTable(), 'status')) {
                $newPage->status = 'draft';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($newPage->getTable(), 'published_at')) {
                $newPage->published_at = null;
            }
            $newPage->save();

            // 2) Deep-clone every PageSection. Sorting by parent_section_id NULL
            //    first guarantees parents are processed before their children,
            //    so the idMap is populated in time to rewrite parent_section_id
            //    on the children. Works for arbitrary depth because IDs
            //    auto-increment monotonically (parent.id < child.id).
            $sections = PageSection::where('sectionable_type', $type)
                ->where('sectionable_id', $id)
                ->orderByRaw('parent_section_id IS NULL DESC, parent_section_id ASC')
                ->orderBy('order')
                ->get();

            $idMap = [];
            foreach ($sections as $sec) {
                $copy = $sec->replicate();
                $copy->sectionable_type = $type;
                $copy->sectionable_id = $newPage->id;
                $copy->parent_section_id = $sec->parent_section_id
                    ? ($idMap[$sec->parent_section_id] ?? null)
                    : null;
                $copy->save();
                $idMap[$sec->id] = $copy->id;

                // Spatie media on the section (if any).
                if (method_exists($sec, 'getMedia')) {
                    foreach ($sec->getMedia() as $m) {
                        try { $m->copy($copy, $m->collection_name); } catch (\Throwable $e) {}
                    }
                }
            }

            // 3) Spatie media on the Page itself (featured_image, gallery, …).
            if (method_exists($original, 'getMedia')) {
                foreach ($original->getMedia() as $m) {
                    try { $m->copy($newPage, $m->collection_name); } catch (\Throwable $e) {}
                }
            }
        });

        $this->loadPages();
        session()->flash('success',
            'Η σελίδα "'.($original->title ?? '#'.$id).'" αντιγράφηκε ως "'.$newPage->title.'".'
        );
    }

    public function render()
    {
        return view('livewire.admin.page-sections.page-selector')
            ->layout('layouts.admin-clean');
    }
}
