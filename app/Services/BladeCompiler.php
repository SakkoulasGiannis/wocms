<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BladeCompiler
{
    protected array $allowedPaths = [
        'resources/views/frontend',
        'resources/views/components',
    ];

    protected array $disallowedFiles = [
        'resources/views/frontend/layout.blade.php',
        'resources/views/layouts/app.blade.php',
    ];

    /**
     * Apply operations to a file
     */
    public function apply(array $operations, string $filePath): array
    {
        // Validate file path
        if (!$this->isAllowedFile($filePath)) {
            return [
                'success' => false,
                'error' => 'File is not allowed for modification',
                'file' => $filePath
            ];
        }

        $fullPath = base_path($filePath);

        if (!File::exists($fullPath)) {
            return [
                'success' => false,
                'error' => 'File does not exist',
                'file' => $filePath
            ];
        }

        // Create backup
        $backupPath = $this->createBackup($fullPath);

        $appliedOperations = [];
        $failedOperation = null;

        try {
            foreach ($operations as $index => $operation) {
                Log::info('Applying operation', [
                    'index' => $index,
                    'action' => $operation['action'] ?? 'unknown',
                    'file' => $filePath
                ]);

                $result = $this->applyOperation($operation, $fullPath);

                if (!$result['success']) {
                    $failedOperation = $index;
                    throw new \Exception($result['error']);
                }

                $appliedOperations[] = $operation;

                // Validate Blade syntax after each operation
                if (!$this->isValidBlade($fullPath)) {
                    throw new \Exception("Invalid Blade syntax after operation #{$index}");
                }
            }

            return [
                'success' => true,
                'message' => 'All operations applied successfully',
                'operations_count' => count($appliedOperations),
                'backup' => $backupPath,
            ];

        } catch (\Exception $e) {
            // Rollback to backup
            $this->rollback($fullPath, $backupPath);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'failed_at_operation' => $failedOperation,
                'applied_operations' => count($appliedOperations),
                'rollback' => true,
            ];
        }
    }

    /**
     * Apply a single operation
     */
    protected function applyOperation(array $operation, string $filePath): array
    {
        $action = $operation['action'] ?? null;

        if (!$action) {
            return ['success' => false, 'error' => 'Missing action'];
        }

        return match($action) {
            'insert_before' => $this->insertBefore($operation, $filePath),
            'insert_after' => $this->insertAfter($operation, $filePath),
            'replace' => $this->replace($operation, $filePath),
            'remove' => $this->remove($operation, $filePath),
            'wrap_with' => $this->wrapWith($operation, $filePath),
            'add_section' => $this->addSection($operation, $filePath),
            default => ['success' => false, 'error' => "Unknown action: {$action}"]
        };
    }

    /**
     * Insert content before a target
     */
    protected function insertBefore(array $operation, string $filePath): array
    {
        $target = $operation['target'] ?? null;
        $content = $operation['content'] ?? null;

        if (!$target || $content === null) {
            return ['success' => false, 'error' => 'Missing target or content'];
        }

        $fileContent = File::get($filePath);

        if (!str_contains($fileContent, $target)) {
            return ['success' => false, 'error' => "Target not found: {$target}"];
        }

        $newContent = str_replace($target, $content . "\n" . $target, $fileContent);
        File::put($filePath, $newContent);

        return ['success' => true];
    }

    /**
     * Insert content after a target
     */
    protected function insertAfter(array $operation, string $filePath): array
    {
        $target = $operation['target'] ?? null;
        $content = $operation['content'] ?? null;

        if (!$target || $content === null) {
            return ['success' => false, 'error' => 'Missing target or content'];
        }

        $fileContent = File::get($filePath);

        if (!str_contains($fileContent, $target)) {
            return ['success' => false, 'error' => "Target not found: {$target}"];
        }

        $newContent = str_replace($target, $target . "\n" . $content, $fileContent);
        File::put($filePath, $newContent);

        return ['success' => true];
    }

    /**
     * Replace content
     */
    protected function replace(array $operation, string $filePath): array
    {
        $target = $operation['target'] ?? null;
        $replacement = $operation['with'] ?? null;

        if (!$target || $replacement === null) {
            return ['success' => false, 'error' => 'Missing target or replacement'];
        }

        $fileContent = File::get($filePath);

        if (!str_contains($fileContent, $target)) {
            return ['success' => false, 'error' => "Target not found: {$target}"];
        }

        $newContent = str_replace($target, $replacement, $fileContent);
        File::put($filePath, $newContent);

        return ['success' => true];
    }

    /**
     * Remove content
     */
    protected function remove(array $operation, string $filePath): array
    {
        $target = $operation['target'] ?? null;

        if (!$target) {
            return ['success' => false, 'error' => 'Missing target'];
        }

        $fileContent = File::get($filePath);

        if (!str_contains($fileContent, $target)) {
            return ['success' => false, 'error' => "Target not found: {$target}"];
        }

        $newContent = str_replace($target, '', $fileContent);
        File::put($filePath, $newContent);

        return ['success' => true];
    }

    /**
     * Wrap content with tags
     */
    protected function wrapWith(array $operation, string $filePath): array
    {
        $target = $operation['target'] ?? null;
        $wrapper = $operation['wrapper'] ?? null;

        if (!$target || !$wrapper) {
            return ['success' => false, 'error' => 'Missing target or wrapper'];
        }

        // Extract opening and closing tags
        preg_match('/<([a-z0-9\-]+)([^>]*)>/i', $wrapper, $matches);
        if (!$matches) {
            return ['success' => false, 'error' => 'Invalid wrapper HTML'];
        }

        $tagName = $matches[1];
        $attributes = $matches[2];
        $openTag = "<{$tagName}{$attributes}>";
        $closeTag = "</{$tagName}>";

        $fileContent = File::get($filePath);

        if (!str_contains($fileContent, $target)) {
            return ['success' => false, 'error' => "Target not found: {$target}"];
        }

        $wrapped = $openTag . "\n    " . $target . "\n" . $closeTag;
        $newContent = str_replace($target, $wrapped, $fileContent);
        File::put($filePath, $newContent);

        return ['success' => true];
    }

    /**
     * Add a new section to Blade file
     */
    protected function addSection(array $operation, string $filePath): array
    {
        $sectionName = $operation['name'] ?? null;
        $content = $operation['content'] ?? null;
        $position = $operation['position'] ?? 'end'; // 'start', 'end', or 'before:section_name'

        if (!$sectionName || $content === null) {
            return ['success' => false, 'error' => 'Missing section name or content'];
        }

        $fileContent = File::get($filePath);

        // Check if section already exists
        if (str_contains($fileContent, "@section('{$sectionName}')")) {
            return ['success' => false, 'error' => "Section '{$sectionName}' already exists"];
        }

        $section = "@section('{$sectionName}')\n{$content}\n@endsection\n";

        if ($position === 'end') {
            $fileContent .= "\n" . $section;
        } elseif ($position === 'start') {
            $fileContent = $section . "\n" . $fileContent;
        } elseif (str_starts_with($position, 'before:')) {
            $targetSection = str_replace('before:', '', $position);
            $target = "@section('{$targetSection}')";

            if (!str_contains($fileContent, $target)) {
                return ['success' => false, 'error' => "Target section not found: {$targetSection}"];
            }

            $fileContent = str_replace($target, $section . "\n" . $target, $fileContent);
        }

        File::put($filePath, $fileContent);

        return ['success' => true];
    }

    /**
     * Create backup of file
     */
    protected function createBackup(string $filePath): string
    {
        $backupDir = storage_path('app/blade-backups');

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filename = basename($filePath);
        $timestamp = date('Y-m-d_H-i-s');
        $backupPath = "{$backupDir}/{$filename}.{$timestamp}.backup";

        File::copy($filePath, $backupPath);

        Log::info('Backup created', ['original' => $filePath, 'backup' => $backupPath]);

        return $backupPath;
    }

    /**
     * Rollback to backup
     */
    protected function rollback(string $filePath, string $backupPath): void
    {
        if (File::exists($backupPath)) {
            File::copy($backupPath, $filePath);
            Log::info('Rolled back to backup', ['file' => $filePath, 'backup' => $backupPath]);
        }
    }

    /**
     * Check if file is allowed for modification
     */
    protected function isAllowedFile(string $filePath): bool
    {
        // Check if file is in disallowed list
        foreach ($this->disallowedFiles as $disallowed) {
            if (str_contains($filePath, $disallowed)) {
                return false;
            }
        }

        // Check if file is in allowed paths
        foreach ($this->allowedPaths as $allowed) {
            if (str_starts_with($filePath, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Basic Blade syntax validation
     */
    protected function isValidBlade(string $filePath): bool
    {
        $content = File::get($filePath);

        // Check for unclosed Blade directives
        $openDirectives = preg_match_all('/@(if|foreach|for|while|switch|section|component|push|unless|isset|empty|auth|guest|env|production|hasSection)\b/', $content);
        $closeDirectives = preg_match_all('/@(endif|endforeach|endfor|endwhile|endswitch|endsection|endcomponent|endpush|endunless|endisset|endempty|endauth|endguest|endenv|endproduction|endhasSection)\b/', $content);

        // Basic check - should have roughly equal opens and closes
        // This is not perfect but catches major issues
        return abs($openDirectives - $closeDirectives) <= 2;
    }

    /**
     * Preview operations without applying them
     */
    public function preview(array $operations, string $filePath): array
    {
        $fullPath = base_path($filePath);

        if (!File::exists($fullPath)) {
            return [
                'success' => false,
                'error' => 'File does not exist'
            ];
        }

        $originalContent = File::get($fullPath);
        $tempFile = tempnam(sys_get_temp_dir(), 'blade_preview_');
        File::put($tempFile, $originalContent);

        $previews = [];

        try {
            foreach ($operations as $index => $operation) {
                $before = File::get($tempFile);
                $result = $this->applyOperation($operation, $tempFile);

                if ($result['success']) {
                    $after = File::get($tempFile);
                    $previews[] = [
                        'operation' => $index,
                        'action' => $operation['action'],
                        'success' => true,
                        'diff' => $this->generateDiff($before, $after),
                    ];
                } else {
                    $previews[] = [
                        'operation' => $index,
                        'action' => $operation['action'],
                        'success' => false,
                        'error' => $result['error'],
                    ];
                }
            }

            return [
                'success' => true,
                'previews' => $previews,
                'final_content' => File::get($tempFile),
            ];

        } finally {
            File::delete($tempFile);
        }
    }

    /**
     * Generate simple diff
     */
    protected function generateDiff(string $before, string $after): array
    {
        $beforeLines = explode("\n", $before);
        $afterLines = explode("\n", $after);

        $diff = [];
        $maxLines = max(count($beforeLines), count($afterLines));

        for ($i = 0; $i < $maxLines; $i++) {
            $beforeLine = $beforeLines[$i] ?? '';
            $afterLine = $afterLines[$i] ?? '';

            if ($beforeLine !== $afterLine) {
                if ($beforeLine && !$afterLine) {
                    $diff[] = ['type' => 'removed', 'line' => $i + 1, 'content' => $beforeLine];
                } elseif (!$beforeLine && $afterLine) {
                    $diff[] = ['type' => 'added', 'line' => $i + 1, 'content' => $afterLine];
                } else {
                    $diff[] = ['type' => 'modified', 'line' => $i + 1, 'before' => $beforeLine, 'after' => $afterLine];
                }
            }
        }

        return $diff;
    }
}
