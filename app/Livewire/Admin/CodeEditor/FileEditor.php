<?php

namespace App\Livewire\Admin\CodeEditor;

use Livewire\Component;
use Illuminate\Support\Facades\File;

class FileEditor extends Component
{
    public $files = [];
    public $selectedFile = '';
    public $fileContent = '';
    public $originalContent = '';
    public $isDirty = false;

    protected $listeners = ['fileSelected'];

    public function mount()
    {
        // Define editable files
        $this->files = [
            'Layout' => resource_path('views/frontend/layout.blade.php'),
            'Header' => resource_path('views/frontend/partials/header.blade.php'),
            'Footer' => resource_path('views/frontend/partials/footer.blade.php'),
        ];

        // Select first file by default
        $this->selectedFile = array_values($this->files)[0];
        $this->loadFile();
    }

    public function selectFile($filePath)
    {
        // Check if current file has unsaved changes
        if ($this->isDirty) {
            $this->dispatch('confirmFileChange', filePath: $filePath);
            return;
        }

        $this->selectedFile = $filePath;
        $this->loadFile();
    }

    public function loadFile()
    {
        if (!$this->selectedFile || !File::exists($this->selectedFile)) {
            $this->fileContent = '';
            $this->originalContent = '';
            $this->isDirty = false;
            return;
        }

        $this->fileContent = File::get($this->selectedFile);
        $this->originalContent = $this->fileContent;
        $this->isDirty = false;
    }

    public function save()
    {
        if (!$this->selectedFile) {
            session()->flash('error', 'No file selected');
            return;
        }

        try {
            // Create backup before saving
            $backupPath = $this->selectedFile . '.backup-' . date('Y-m-d-His');
            File::copy($this->selectedFile, $backupPath);

            // Save the file
            File::put($this->selectedFile, $this->fileContent);

            $this->originalContent = $this->fileContent;
            $this->isDirty = false;

            // Clear view cache
            \Artisan::call('view:clear');

            // Dispatch event to update frontend
            $this->dispatch('fileSaved');

            session()->flash('success', 'File saved successfully! Backup created at: ' . basename($backupPath));
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving file: ' . $e->getMessage());
        }
    }

    public function resetContent()
    {
        $this->fileContent = $this->originalContent;
        $this->isDirty = false;
    }

    public function restoreBackup($backupFile)
    {
        try {
            if (!File::exists($backupFile)) {
                session()->flash('error', 'Backup file not found');
                return;
            }

            $this->fileContent = File::get($backupFile);
            session()->flash('success', 'Backup restored. Click Save to apply changes.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error restoring backup: ' . $e->getMessage());
        }
    }

    public function getBackups()
    {
        if (!$this->selectedFile) {
            return [];
        }

        $directory = dirname($this->selectedFile);
        $filename = basename($this->selectedFile);
        $backupPattern = $filename . '.backup-*';

        $backups = File::glob($directory . '/' . $backupPattern);

        // Sort by modified time (newest first)
        usort($backups, function($a, $b) {
            return File::lastModified($b) - File::lastModified($a);
        });

        return array_map(function($backup) {
            return [
                'path' => $backup,
                'name' => basename($backup),
                'date' => date('Y-m-d H:i:s', File::lastModified($backup)),
                'size' => $this->formatBytes(File::size($backup)),
            ];
        }, array_slice($backups, 0, 10)); // Show last 10 backups
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function render()
    {
        return view('livewire.admin.code-editor.file-editor', [
            'backups' => $this->getBackups(),
        ])->layout('layouts.admin-clean');
    }
}
