<?php

namespace App\Console\Commands;

use App\Services\SectionTemplateExporter;
use Illuminate\Console\Command;

class ImportSectionTemplates extends Command
{
    protected $signature = 'section-templates:import
        {file : Path to the JSON export file}
        {--overwrite : Overwrite existing templates with same slug}';

    protected $description = 'Import section templates with fields from JSON file';

    public function handle(SectionTemplateExporter $exporter): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            $this->error('Invalid JSON file');

            return self::FAILURE;
        }

        $overwrite = $this->option('overwrite');

        if ($overwrite) {
            $this->warn('Overwrite mode enabled - existing templates will be updated');
        }

        $this->info('Found '.count($data).' template(s) in file');

        $stats = $exporter->import($data, $overwrite);

        $this->info("Created: {$stats['created']}");
        $this->info("Updated: {$stats['updated']}");
        $this->info("Skipped: {$stats['skipped']}");

        return self::SUCCESS;
    }
}
