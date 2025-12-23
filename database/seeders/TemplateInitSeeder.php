<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class TemplateInitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder automatically fixes any templates that are missing model_class or table_name
     */
    public function run(): void
    {
        $this->command->info('Initializing templates...');

        // Run the templates:fix command
        Artisan::call('templates:fix');

        $this->command->info('Templates initialized successfully!');
    }
}
