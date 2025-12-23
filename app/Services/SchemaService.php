<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class SchemaService
{
    protected string $schemasPath;

    public function __construct()
    {
        $this->schemasPath = config_path('schemas');
    }

    public function getSchema(string $entity): ?array
    {
        $path = "{$this->schemasPath}/{$entity}.json";

        if (!File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        return json_decode($content, true);
    }

    public function getMenu(): array
    {
        return $this->getSchema('menu') ?? ['admin_menu' => []];
    }

    public function getFormFields(string $entity): array
    {
        $schema = $this->getSchema($entity);
        return $schema['form']['sections'] ?? [];
    }

    public function getTableColumns(string $entity): array
    {
        $schema = $this->getSchema($entity);
        return $schema['table']['columns'] ?? [];
    }

    public function getFilters(string $entity): array
    {
        $schema = $this->getSchema($entity);
        return $schema['table']['filters'] ?? [];
    }

    public function getAllSchemas(): array
    {
        $schemas = [];
        $files = File::files($this->schemasPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'json' && $file->getFilename() !== 'menu.json') {
                $entity = $file->getFilenameWithoutExtension();
                $schemas[$entity] = $this->getSchema($entity);
            }
        }

        return $schemas;
    }
}
