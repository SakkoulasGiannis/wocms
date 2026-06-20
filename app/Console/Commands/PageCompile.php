<?php

namespace App\Console\Commands;

use App\Services\PageCompiler;
use Illuminate\Console\Command;

/**
 * Compile a JSON page spec into a Page + sections.
 *
 *   php artisan page:compile path/to/spec.json
 *   cat spec.json | php artisan page:compile --stdin
 *   php artisan page:compile - < spec.json     # also stdin (POSIX convention)
 *
 * Exits 0 on success, 1 on failure. Output is a JSON result with
 *   { ok, created, page_id, slug, url, sections_touched, warnings, error? }
 */
class PageCompile extends Command
{
    protected $signature = 'page:compile {path? : Path to JSON spec, or "-" for stdin}
                                         {--stdin : Read JSON from stdin}';

    protected $description = 'Compile a JSON page spec into a real Page + PageSections';

    public function handle(): int
    {
        $json = $this->readInput();
        if ($json === null) {
            $this->error('No JSON provided. Pass a path argument, "-", or --stdin.');

            return self::FAILURE;
        }

        try {
            $result = PageCompiler::fromJson($json)->compile();
        } catch (\Throwable $e) {
            $this->error('Compile error: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    protected function readInput(): ?string
    {
        $path = $this->argument('path');
        if ($this->option('stdin') || $path === '-') {
            $json = '';
            while (! feof(STDIN)) {
                $json .= fread(STDIN, 8192);
            }

            return trim($json) === '' ? null : $json;
        }
        if (! $path) {
            return null;
        }
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return null;
        }

        return file_get_contents($path);
    }
}
