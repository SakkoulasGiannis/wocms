<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EnsureEditableFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ensure-editable-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure editable layout/header/footer files exist (created from defaults if missing)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking editable files...');
        $this->newLine();

        $partialsPath = resource_path('views/frontend/partials');
        $frontendPath = resource_path('views/frontend');

        $files = [
            [
                'default' => $frontendPath . '/default-layout.blade.php',
                'editable' => $frontendPath . '/layout.blade.php',
                'name' => 'Layout',
            ],
            [
                'default' => $partialsPath . '/default-header.blade.php',
                'editable' => $partialsPath . '/header.blade.php',
                'name' => 'Header',
            ],
            [
                'default' => $partialsPath . '/default-footer.blade.php',
                'editable' => $partialsPath . '/footer.blade.php',
                'name' => 'Footer',
            ],
        ];

        $created = 0;
        $existing = 0;
        $missing = 0;

        foreach ($files as $file) {
            if (File::exists($file['editable'])) {
                $this->line("  ✓ {$file['name']}: Already exists");
                $existing++;
            } elseif (File::exists($file['default'])) {
                File::copy($file['default'], $file['editable']);
                $this->info("  ✅ {$file['name']}: Created from default");
                $created++;
            } else {
                $this->error("  ❌ {$file['name']}: Default file missing!");
                $missing++;
            }
        }

        $this->newLine();

        if ($created > 0) {
            $this->info("✅ Created {$created} file(s) from defaults");
        }

        if ($existing > 0) {
            $this->line("ℹ️  {$existing} file(s) already exist");
        }

        if ($missing > 0) {
            $this->error("⚠️  {$missing} default file(s) missing - please check your repository");
            return 1;
        }

        $this->newLine();
        $this->info('🎯 All editable files are in place!');

        return 0;
    }
}
