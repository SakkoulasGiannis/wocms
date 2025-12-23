<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Template;
use App\Services\TemplateTableGenerator;

class FixTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'templates:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix templates that are missing model_class or table_name';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking all templates...');

        $templates = Template::where('is_active', true)->get();
        $fixed = 0;

        foreach ($templates as $template) {
            // Skip templates that don't require database
            if (!$template->requires_database) {
                $this->line("  ⏭️  {$template->name} - doesn't require database, skipping");
                continue;
            }

            // Check if model_class or table_name is missing
            if (empty($template->model_class) || empty($template->table_name)) {
                $this->warn("  🔧 Fixing {$template->name}...");

                $generator = new TemplateTableGenerator();
                $success = $generator->createTableAndModel($template->fresh());

                if ($success) {
                    $template->refresh();
                    $this->info("     ✓ Created model: {$template->model_class}");
                    $this->info("     ✓ Created table: {$template->table_name}");
                    $fixed++;
                } else {
                    $this->error("     ✗ Failed to fix {$template->name}");
                }
            } else {
                $this->line("  ✓ {$template->name} - OK");
            }
        }

        $this->newLine();
        if ($fixed > 0) {
            $this->info("✅ Fixed {$fixed} template(s)!");
        } else {
            $this->info("✅ All templates are OK!");
        }

        return Command::SUCCESS;
    }
}
