<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\PageBuilder\Models\PageSection;

/**
 * Detect and heal wysiwyg PageSection rows whose `content.content` payload
 * has been double-JSON-encoded — meaning the inner EditorJS structure was
 * serialised as a STRING and then re-escaped, so values like literal `\n`,
 * `\"`, `<\/SPAN>` end up rendered as text on the frontend instead of being
 * proper newlines / quotes / forward slashes.
 *
 * The corruption pattern looks like:
 *   $section->content === [
 *     'content' => '{"time":..., "blocks":[ ... "data":{"html":"<div ...>\\n  <p>...\\\"\\/SPAN>..."} ... ]}'
 *   ]
 * That inner JSON has DOUBLE-escaped chars: `\\n` literally appears as `\n`
 * (backslash + n, 2 chars) in the parsed `data.html`, instead of a newline.
 *
 * Usage:
 *   php artisan page:heal-content                       # report only (safe)
 *   php artisan page:heal-content --page=3              # narrow to one page
 *   php artisan page:heal-content --apply                # actually write fixes
 *   php artisan page:heal-content --page=3 --apply       # heal page 3
 */
class PageHealContent extends Command
{
    protected $signature = 'page:heal-content
                            {--page= : Restrict to a specific page id or slug}
                            {--apply : Actually persist fixes (default is dry-run)}';

    protected $description = 'Repair double-JSON-encoded wysiwyg section content (heals literal \\n / \\" / \\/ artefacts)';

    public function handle(): int
    {
        $query = PageSection::where('sectionable_type', Page::class)
            ->where('section_type', 'like', '%wysiwyg%');

        if ($pageArg = $this->option('page')) {
            $page = is_numeric($pageArg) ? Page::find($pageArg) : Page::where('slug', $pageArg)->first();
            if (! $page) {
                $this->error("Page not found: {$pageArg}");

                return self::FAILURE;
            }
            $query->where('sectionable_id', $page->id);
            $this->info("Scanning sections of page #{$page->id} ({$page->slug})…");
        } else {
            $this->info('Scanning all wysiwyg page sections…');
        }

        $apply = $this->option('apply');
        $rows = $query->get();
        $this->info("Found {$rows->count()} candidates.");

        $healed = 0;
        $clean = 0;
        $skipped = 0;

        foreach ($rows as $section) {
            $content = $section->content;
            if (! is_array($content) || ! isset($content['content']) || ! is_string($content['content'])) {
                // Already proper array shape or empty — nothing to do.
                $clean++;

                continue;
            }

            $inner = $content['content'];

            // Heuristic: if the html field doesn't contain literal `\n` /
            // `\"` /  `\/` we're not double-encoded. Only act when we see
            // the corruption fingerprints.
            if (! preg_match('/\\\\n|\\\\"|\\\\\\//', $inner)) {
                $skipped++;

                continue;
            }

            $healedInner = $this->healInnerJson($inner);
            if ($healedInner === $inner) {
                $skipped++;

                continue;
            }

            $this->line("• Section #{$section->id} (page {$section->sectionable_id}) — corruption detected");
            $this->line('  before: '.mb_substr($inner, 0, 140).'…');
            $this->line('  after:  '.mb_substr($healedInner, 0, 140).'…');

            if ($apply) {
                DB::transaction(function () use ($section, $content, $healedInner) {
                    $newContent = $content;
                    $newContent['content'] = $healedInner;
                    $section->content = $newContent;
                    $section->save();
                });
                $healed++;
            }
        }

        $this->newLine();
        if ($apply) {
            $this->info("✓ Healed {$healed} sections. {$clean} already clean. {$skipped} skipped (no fingerprint match).");
        } else {
            $this->info("✓ Dry-run complete. Would heal {$healed} sections. {$clean} already clean. {$skipped} skipped.");
            $this->line('Re-run with <comment>--apply</comment> to persist the changes.');
        }

        return self::SUCCESS;
    }

    /**
     * Unescape a doubly-JSON-encoded EditorJS payload. We don't json_decode
     * the whole thing — the structure is supposed to stay as a JSON STRING
     * (that's what content.content expects). We just need to drop the EXTRA
     * layer of backslash escaping that was applied on top.
     *
     * Specifically: `\\n` → `\n`, `\\"` → `\"`, `\\/` → `\/`, `\\\\` → `\\`.
     * After healing, the string is still valid JSON; downstream json_decode
     * inside EditorJsRenderer will then produce the intended html, quotes,
     * and forward slashes.
     */
    protected function healInnerJson(string $inner): string
    {
        // IMPORTANT: order matters. Heal `\\\\` (4 backslashes = doubled
        // literal-backslash) first, then targeted escape pairs, so we don't
        // partial-match overlapping patterns.
        $out = str_replace(
            ['\\\\\\\\', '\\\\n', '\\\\"', '\\\\/'],
            ['\\\\',     '\\n',   '\\"',   '\\/'],
            $inner,
        );

        // Validate: result must still be json_decodable. If we somehow broke
        // it, return the original untouched.
        $probe = json_decode($out, true);
        if (! is_array($probe) || ! isset($probe['blocks'])) {
            return $inner;
        }

        return $out;
    }
}
