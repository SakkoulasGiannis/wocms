<?php

namespace App\Console\Commands;

use App\Services\SectionTemplateExporter;
use Illuminate\Console\Command;

class ExportSectionTemplates extends Command
{
    protected $signature = 'section-templates:export
        {--slug=* : Export specific templates by slug (omit for all)}
        {--output= : Output file path (default: storage/app/section-templates-export.json)}';

    protected $description = 'Export section templates with fields to JSON for deployment';

    public function handle(SectionTemplateExporter $exporter): int
    {
        $slugs = $this->option('slug');
        $output = $this->option('output') ?: storage_path('app/section-templates-export.json');

        if (! empty($slugs)) {
            $data = $exporter->exportBySlugs($slugs);
            $this->info('Exporting '.count($data).' template(s): '.implode(', ', $slugs));
        } else {
            $data = $exporter->exportAll();
            $this->info('Exporting all '.count($data).' templates');
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($output, $json);

        $this->info("Exported to: {$output}");
        $this->info('File size: '.number_format(strlen($json)).' bytes');

        return self::SUCCESS;
    }
}
