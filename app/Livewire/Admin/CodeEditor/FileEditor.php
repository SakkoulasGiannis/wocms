<?php

namespace App\Livewire\Admin\CodeEditor;

use Illuminate\Support\Facades\File;
use Livewire\Component;

class FileEditor extends Component
{
    public $files = [];

    public $selectedFile = '';

    public $fileContent = '';

    public $originalContent = '';

    public $isDirty = false;

    public $showCreateModal = false;

    public $createType = 'file'; // 'file' or 'folder'

    public $createName = '';

    public $createDirectory = '';

    public $createContent = '';

    public $availableDirectories = [];

    protected $listeners = ['fileSelected'];

    public function mount()
    {
        // Define editable files grouped by category
        $this->files = $this->getEditableFiles();

        // Select first file by default
        $this->selectedFile = array_values($this->files)[0]['path'];
        $this->loadFile();
    }

    protected function getEditableFiles()
    {
        $files = [];

        // Core Layout Files
        $files[] = [
            'name' => 'Layout',
            'path' => resource_path('views/frontend/layout.blade.php'),
            'category' => 'Core',
            'description' => 'Main layout wrapper',
        ];
        $files[] = [
            'name' => 'Header',
            'path' => resource_path('views/frontend/partials/header.blade.php'),
            'category' => 'Core',
            'description' => 'Site header/navigation',
        ];
        $files[] = [
            'name' => 'Footer',
            'path' => resource_path('views/frontend/partials/footer.blade.php'),
            'category' => 'Core',
            'description' => 'Site footer',
        ];

        // Template Files (from templates table)
        $templates = \App\Models\Template::where('has_physical_file', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($templates as $template) {
            // Check if template file exists
            $templatePath = resource_path('views/templates/'.$template->slug.'.blade.php');

            if (File::exists($templatePath)) {
                $files[] = [
                    'name' => $template->name,
                    'path' => $templatePath,
                    'category' => 'Templates',
                    'description' => $template->description ?: 'Template: '.$template->slug,
                ];
            }
        }

        // Frontend Template Files (generated templates)
        $generatedTemplates = File::glob(resource_path('views/frontend/templates/*.blade.php'));
        foreach ($generatedTemplates as $templatePath) {
            $filename = basename($templatePath, '.blade.php');
            $files[] = [
                'name' => ucfirst(str_replace(['-', '_'], ' ', $filename)),
                'path' => $templatePath,
                'category' => 'Generated',
                'description' => 'Auto-generated template',
            ];
        }

        // Frontend Partials
        $partials = File::glob(resource_path('views/frontend/partials/*.blade.php'));
        foreach ($partials as $partialPath) {
            $filename = basename($partialPath, '.blade.php');
            // Skip if already added (header/footer)
            if (in_array($partialPath, array_column($files, 'path'))) {
                continue;
            }
            $files[] = [
                'name' => ucfirst(str_replace(['-', '_'], ' ', $filename)),
                'path' => $partialPath,
                'category' => 'Partials',
                'description' => 'Partial template',
            ];
        }

        return $files;
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
        if (! $this->selectedFile || ! File::exists($this->selectedFile)) {
            $this->fileContent = '';
            $this->originalContent = '';
            $this->isDirty = false;

            return;
        }

        $this->fileContent = File::get($this->selectedFile);
        $this->originalContent = $this->fileContent;
        $this->isDirty = false;

        // Dispatch event to update Monaco Editor
        $this->dispatch('fileLoaded', content: $this->fileContent);
    }

    public function updatedFileContent()
    {
        $this->isDirty = $this->fileContent !== $this->originalContent;
    }

    public function save()
    {
        \Log::info('💾 Save method called', [
            'selectedFile' => $this->selectedFile,
            'fileContentLength' => strlen($this->fileContent ?? ''),
            'isDirty' => $this->isDirty,
            'fileContent_first100' => substr($this->fileContent ?? '', 0, 100),
            'fileContent_md5' => md5($this->fileContent ?? ''),
        ]);

        if (! $this->selectedFile) {
            \Log::warning('❌ Save failed: No file selected');
            session()->flash('error', 'No file selected');

            return;
        }

        try {
            // Log the EXACT path we're trying to save
            $realPath = realpath($this->selectedFile);
            \Log::info('📂 File paths:', [
                'selectedFile' => $this->selectedFile,
                'realpath' => $realPath,
                'basename' => basename($this->selectedFile),
                'dirname' => dirname($this->selectedFile),
            ]);

            // Check if file is writable
            if (! is_writable($this->selectedFile)) {
                throw new \Exception('File is not writable. Please check permissions: '.$this->selectedFile);
            }

            // Check if directory is writable (for backup)
            $directory = dirname($this->selectedFile);
            if (! is_writable($directory)) {
                throw new \Exception('Directory is not writable. Please check permissions: '.$directory);
            }

            // Get file hash BEFORE save
            $hashBefore = md5_file($this->selectedFile);
            $sizeBefore = filesize($this->selectedFile);
            \Log::info('📊 File state BEFORE save:', [
                'size' => $sizeBefore,
                'hash' => $hashBefore,
                'modified' => date('Y-m-d H:i:s', filemtime($this->selectedFile)),
            ]);

            // Create backup before saving
            $backupPath = $this->selectedFile.'.backup-'.date('Y-m-d-His');
            File::copy($this->selectedFile, $backupPath);
            \Log::info('✅ Backup created: '.basename($backupPath));

            // Save the file
            $bytesWritten = File::put($this->selectedFile, $this->fileContent);

            if ($bytesWritten === false) {
                throw new \Exception('Failed to write to file. File::put returned false.');
            }

            \Log::info('✅ File::put executed - returned: '.$bytesWritten.' bytes');

            // Clear OPcache for this file (CRITICAL for live server)
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($this->selectedFile, true);
                \Log::info('✅ OPcache invalidated for file');
            } else {
                \Log::warning('⚠️ opcache_invalidate function not available');
            }

            // Verify the content was actually written
            clearstatcache(true, $this->selectedFile);

            // Get file hash AFTER save
            $hashAfter = md5_file($this->selectedFile);
            $sizeAfter = filesize($this->selectedFile);
            $modifiedAfter = filemtime($this->selectedFile);

            \Log::info('📊 File state AFTER save:', [
                'size' => $sizeAfter,
                'hash' => $hashAfter,
                'modified' => date('Y-m-d H:i:s', $modifiedAfter),
                'hash_changed' => $hashBefore !== $hashAfter ? 'YES' : 'NO',
                'size_changed' => $sizeBefore !== $sizeAfter ? 'YES' : 'NO',
            ]);

            // Read back the content
            $savedContent = File::get($this->selectedFile);
            $savedHash = md5($savedContent);
            $expectedHash = md5($this->fileContent);

            if ($savedContent !== $this->fileContent) {
                \Log::error('❌ FILE CONTENT MISMATCH!', [
                    'expected_hash' => $expectedHash,
                    'saved_hash' => $savedHash,
                    'expected_length' => strlen($this->fileContent),
                    'saved_length' => strlen($savedContent),
                    'first_100_chars_expected' => substr($this->fileContent, 0, 100),
                    'first_100_chars_saved' => substr($savedContent, 0, 100),
                ]);
            } else {
                \Log::info('✅ File content verified - matches saved content');
            }

            $this->originalContent = $this->fileContent;
            $this->isDirty = false;

            // Clear view cache
            \Artisan::call('view:clear');
            \Log::info('✅ View cache cleared');

            session()->flash('success', 'File saved successfully! Backup created at: '.basename($backupPath).' ('.$bytesWritten.' bytes written)');
            \Log::info('✅ Save completed successfully');
        } catch (\Exception $e) {
            \Log::error('❌ Save failed: '.$e->getMessage(), [
                'exception' => $e,
                'file' => $this->selectedFile,
                'is_writable' => is_writable($this->selectedFile),
                'file_exists' => file_exists($this->selectedFile),
                'file_permissions' => file_exists($this->selectedFile) ? substr(sprintf('%o', fileperms($this->selectedFile)), -4) : 'N/A',
                'directory' => dirname($this->selectedFile),
                'dir_writable' => is_writable(dirname($this->selectedFile)),
                'dir_permissions' => is_dir(dirname($this->selectedFile)) ? substr(sprintf('%o', fileperms(dirname($this->selectedFile))), -4) : 'N/A',
            ]);
            session()->flash('error', 'Error saving file: '.$e->getMessage());
        }
    }

    public function resetContent()
    {
        $this->fileContent = $this->originalContent;
        $this->isDirty = false;

        // Dispatch event to update Monaco Editor
        $this->dispatch('fileLoaded', content: $this->fileContent);
    }

    public function restoreBackup($backupFile)
    {
        try {
            if (! File::exists($backupFile)) {
                session()->flash('error', 'Backup file not found');

                return;
            }

            $this->fileContent = File::get($backupFile);
            $this->isDirty = true;

            // Dispatch event to update Monaco Editor (without updating originalContent)
            $this->dispatch('backupRestored', content: $this->fileContent);

            session()->flash('success', 'Backup restored. Click Save to apply changes.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error restoring backup: '.$e->getMessage());
        }
    }

    public function getBackups()
    {
        if (! $this->selectedFile) {
            return [];
        }

        $directory = dirname($this->selectedFile);
        $filename = basename($this->selectedFile);
        $backupPattern = $filename.'.backup-*';

        $backups = File::glob($directory.'/'.$backupPattern);

        // Sort by modified time (newest first)
        usort($backups, function ($a, $b) {
            return File::lastModified($b) - File::lastModified($a);
        });

        return array_map(function ($backup) {
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

        return round($bytes, $precision).' '.$units[$pow];
    }

    public function openCreateModal(string $type = 'file'): void
    {
        $this->createType = $type;
        $this->createName = '';
        $this->createContent = '';
        $this->createDirectory = resource_path('views/frontend');
        $this->availableDirectories = $this->getWritableDirectories();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['createType', 'createName', 'createContent', 'createDirectory']);
    }

    protected function getWritableDirectories(): array
    {
        $basePaths = [
            resource_path('views/frontend'),
            resource_path('views/frontend/partials'),
            resource_path('views/frontend/templates'),
            resource_path('views/templates'),
            resource_path('views/components'),
        ];

        $dirs = [];
        foreach ($basePaths as $path) {
            if (is_dir($path)) {
                $dirs[$path] = str_replace(base_path().'/', '', $path);

                // Also add subdirectories (1 level deep)
                $subdirs = File::directories($path);
                foreach ($subdirs as $subdir) {
                    $dirs[$subdir] = str_replace(base_path().'/', '', $subdir);
                }
            }
        }

        return $dirs;
    }

    public function createFileOrFolder(): void
    {
        $this->validate([
            'createName' => 'required|string|max:255|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'createDirectory' => 'required|string',
        ]);

        $targetDir = $this->createDirectory;

        if (! is_dir($targetDir) || ! is_writable($targetDir)) {
            session()->flash('error', 'Target directory is not writable.');

            return;
        }

        if ($this->createType === 'folder') {
            $folderPath = $targetDir.'/'.$this->createName;

            if (File::exists($folderPath)) {
                session()->flash('error', 'A folder with this name already exists.');

                return;
            }

            File::makeDirectory($folderPath, 0755, true);
            session()->flash('success', 'Folder created: '.$this->createName);
        } else {
            // Ensure .blade.php extension
            $fileName = $this->createName;
            if (! str_ends_with($fileName, '.blade.php') && ! str_ends_with($fileName, '.php') && ! str_ends_with($fileName, '.css') && ! str_ends_with($fileName, '.js')) {
                $fileName .= '.blade.php';
            }

            $filePath = $targetDir.'/'.$fileName;

            if (File::exists($filePath)) {
                session()->flash('error', 'A file with this name already exists.');

                return;
            }

            $content = $this->createContent ?: '';
            File::put($filePath, $content);

            // Refresh file list and select new file
            $this->files = $this->getEditableFiles();
            $this->selectedFile = $filePath;
            $this->loadFile();

            session()->flash('success', 'File created: '.$fileName);
        }

        // Refresh file list
        $this->files = $this->getEditableFiles();
        $this->closeCreateModal();
    }

    public function deleteCurrentFile(): void
    {
        if (! $this->selectedFile || ! File::exists($this->selectedFile)) {
            session()->flash('error', 'No file selected.');

            return;
        }

        // Prevent deleting core files
        $coreFiles = [
            resource_path('views/frontend/layout.blade.php'),
            resource_path('views/frontend/partials/header.blade.php'),
            resource_path('views/frontend/partials/footer.blade.php'),
        ];

        if (in_array($this->selectedFile, $coreFiles)) {
            session()->flash('error', 'Cannot delete core layout files.');

            return;
        }

        $fileName = basename($this->selectedFile);
        File::delete($this->selectedFile);

        $this->files = $this->getEditableFiles();
        if (! empty($this->files)) {
            $this->selectedFile = $this->files[0]['path'];
            $this->loadFile();
        }

        session()->flash('success', 'File deleted: '.$fileName);
    }

    public function render()
    {
        return view('livewire.admin.code-editor.file-editor', [
            'backups' => $this->getBackups(),
        ])->layout('layouts.admin-clean');
    }
}
