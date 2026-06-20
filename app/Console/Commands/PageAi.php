<?php

namespace App\Console\Commands;

use App\Services\PageBuilderAgent;
use Illuminate\Console\Command;

/**
 * Drive the PageBuilderAgent from the CLI for testing.
 *
 *   php artisan page:ai create --prompt="Φτιάξε σελίδα για μηχανικούς"
 *   php artisan page:ai create --prompt="..." --templates=wysiwyg,hero
 *   php artisan page:ai edit --page=build-your-own-villa --prompt="Άλλαξε X σε Y"
 */
class PageAi extends Command
{
    protected $signature = 'page:ai {action : create|edit}
                                    {--prompt= : User instruction}
                                    {--page= : Page id or slug (edit mode)}
                                    {--templates= : Comma-separated template slugs (create mode, optional)}';

    protected $description = 'Drive the PageBuilderAgent (AI → PageCompiler) from the CLI';

    public function handle(PageBuilderAgent $agent): int
    {
        $action = $this->argument('action');
        $prompt = (string) $this->option('prompt');

        if ($prompt === '') {
            $this->error('Use --prompt="..." to supply the user instruction.');

            return self::FAILURE;
        }

        $result = match ($action) {
            'create' => $agent->createPage(
                userPrompt: $prompt,
                templateSlugs: $this->option('templates')
                                ? array_filter(array_map('trim', explode(',', $this->option('templates'))))
                                : []
            ),
            'edit' => $this->editAction($agent, $prompt),
            default => null,
        };

        if ($result === null) {
            $this->error("Unknown action: {$action} — use 'create' or 'edit'.");

            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    protected function editAction(PageBuilderAgent $agent, string $prompt): ?array
    {
        $page = $this->option('page');
        if (! $page) {
            $this->error('Provide --page=<id|slug> for the edit action.');

            return null;
        }

        return $agent->editPage($page, $prompt);
    }
}
