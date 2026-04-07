<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SectionTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * All 15 system section templates are now created via migration
     * (2026_03_04_000001_create_system_section_templates.php).
     *
     * This seeder is kept as a no-op for backward compatibility
     * with any scripts or pipelines that call it.
     */
    public function run(): void
    {
        $this->command->info('Section templates are managed via migration. Skipping seeder.');
    }
}
