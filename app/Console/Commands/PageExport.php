<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;
use Modules\PageBuilder\Models\PageSection;

/**
 * Export a Page (and its nested PageSections) to a portable JSON spec
 * that the PageCompiler can later round-trip back into the DB.
 *
 *   php artisan page:export 5            # by id
 *   php artisan page:export build-villa  # by slug
 *   php artisan page:export 5 --pretty   # human-readable
 *   php artisan page:export 5 -o out.json
 */
class PageExport extends Command
{
    protected $signature = 'page:export {identifier : Page id or slug}
                                        {--o=    : Write to file instead of stdout}
                                        {--pretty : Pretty-print JSON output}';

    protected $description = 'Export a Page + sections tree to JSON';

    public function handle(): int
    {
        $id = $this->argument('identifier');
        /** @var Page|null $page */
        $page = is_numeric($id)
            ? Page::find($id)
            : Page::where('slug', $id)->first();

        if (! $page) {
            $this->error("Page not found: {$id}");

            return self::FAILURE;
        }

        $spec = $this->exportPage($page);

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
               | ($this->option('pretty') ? JSON_PRETTY_PRINT : 0);
        $json = json_encode($spec, $flags);

        if ($outPath = $this->option('o')) {
            file_put_contents($outPath, $json);
            $this->info("Wrote {$outPath} ({$page->title})");
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }

    /**
     * Build the spec for a Page + recursive sections.
     */
    protected function exportPage(Page $page): array
    {
        $pageData = [
            'title' => $page->title,
            'slug' => $page->slug,
            'status' => $page->status,
            'render_mode' => $page->render_mode,
            'featured_image' => $page->featured_image,
            'body' => $page->body,
            'body_css' => $page->body_css,
        ];

        // SEO fields — collect all seo_*
        $seo = [];
        foreach ($page->getAttributes() as $col => $val) {
            if (str_starts_with($col, 'seo_')) {
                $seo[substr($col, 4)] = $val;
            }
        }
        if (! empty($seo)) {
            $pageData['seo'] = $seo;
        }

        return [
            'type' => 'page',
            'page' => $pageData,
            'sections' => $this->exportSectionsTree(Page::class, $page->id),
        ];
    }

    /**
     * Recursively export the sections tree for a sectionable entity.
     */
    protected function exportSectionsTree(string $sectionableType, int $sectionableId, ?int $parentId = null): array
    {
        $sections = PageSection::where('sectionable_type', $sectionableType)
            ->where('sectionable_id', $sectionableId)
            ->where('parent_section_id', $parentId)
            ->orderBy('order')
            ->get();

        $out = [];
        foreach ($sections as $s) {
            $entry = [
                'id' => $s->id,            // preserved for round-trip edits
                'section_type' => $s->section_type,
                'name' => $s->name,
                'order' => $s->order,
                'scope' => $s->scope,
                'is_active' => $s->is_active,
                'is_visible' => $s->is_visible,
                'section_template_id' => $s->section_template_id,
                'edit_mode' => $s->edit_mode,
                'content' => $s->content,
                'settings' => $s->settings,
                'css' => $s->css,
            ];

            $children = $this->exportSectionsTree($sectionableType, $sectionableId, $s->id);
            if (! empty($children)) {
                $entry['children'] = $children;
            }

            $out[] = $entry;
        }

        return $out;
    }
}
