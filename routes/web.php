<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

// Frontend routes
Route::get('/', [FrontendController::class, 'home'])->name('home');

// Blog routes
Route::get('/blog', [\App\Http\Controllers\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [\App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');

// CSRF token refresh route
Route::get('/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
    ]);
})->name('csrf-token');

// Admin routes (protected by auth middleware)
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Content Tree
    Route::get('/content-tree', \App\Livewire\Admin\ContentTree\TreeViewer::class)->name('content-tree');

    // Livewire Templates Routes
    Route::get('/templates', \App\Livewire\Admin\Templates\TemplateList::class)->name('templates.index');
    Route::get('/templates/create', \App\Livewire\Admin\Templates\TemplateForm::class)->name('templates.create');
    Route::get('/templates/{templateId}/edit', \App\Livewire\Admin\Templates\TemplateForm::class)->name('templates.edit');

    // Forms Routes
    Route::get('/forms', \App\Livewire\Admin\Forms\FormList::class)->name('forms.index');
    Route::get('/forms/create', \App\Livewire\Admin\Forms\FormBuilder::class)->name('forms.create');
    Route::get('/forms/{formId}/edit', \App\Livewire\Admin\Forms\FormBuilder::class)->name('forms.edit');
    Route::get('/forms/{formId}/submissions', \App\Livewire\Admin\Forms\SubmissionList::class)->name('forms.submissions');

    // Settings Route
    Route::get('/settings', \App\Livewire\Admin\Settings\SettingsPage::class)->name('settings');

    // Media Library Route
    Route::get('/media', \App\Livewire\Admin\Media\MediaLibrary::class)->name('media');

    // User Management Routes
    Route::get('/users', \App\Livewire\Admin\Users\UserManagement::class)->name('users');
    Route::get('/users/create', \App\Livewire\Admin\Users\UserEdit::class)->name('users.create');
    Route::get('/users/{userId}/edit', \App\Livewire\Admin\Users\UserEdit::class)->name('users.edit');

    // Roles Management Routes
    Route::get('/roles', \App\Livewire\Admin\Roles\RoleManagement::class)->name('roles');
    Route::get('/roles/create', \App\Livewire\Admin\Roles\RoleEdit::class)->name('roles.create');
    Route::get('/roles/{roleId}/edit', \App\Livewire\Admin\Roles\RoleEdit::class)->name('roles.edit');

    // Permissions Management Route
    Route::get('/permissions', \App\Livewire\Admin\Permissions\PermissionManagement::class)->name('permissions');

    // Page Sections Routes
    Route::get('/page-sections', \App\Livewire\Admin\PageSections\PageSelector::class)->name('page-sections.index');
    Route::get('/page-sections/manage/{pageType}', \App\Livewire\Admin\PageSections\SectionManager::class)->name('page-sections');
    Route::get('/page-sections/section/{sectionId}/edit', \App\Livewire\Admin\PageSections\SectionEditor::class)->name('page-sections.edit');

    // Section Templates Routes
    Route::get('/section-templates', \App\Livewire\Admin\SectionTemplates\SectionTemplateList::class)->name('section-templates.index');
    Route::get('/section-templates/create', \App\Livewire\Admin\SectionTemplates\SectionTemplateForm::class)->name('section-templates.create');
    Route::get('/section-templates/{templateId}/edit', \App\Livewire\Admin\SectionTemplates\SectionTemplateForm::class)->name('section-templates.edit');

    // Module Management Routes
    Route::get('/modules', [\App\Http\Controllers\Admin\ModuleController::class, 'index'])->name('modules.index');
    Route::post('/modules/{module}/enable', [\App\Http\Controllers\Admin\ModuleController::class, 'enable'])->name('modules.enable');
    Route::post('/modules/{module}/disable', [\App\Http\Controllers\Admin\ModuleController::class, 'disable'])->name('modules.disable');
    Route::delete('/modules/{module}', [\App\Http\Controllers\Admin\ModuleController::class, 'delete'])->name('modules.delete');
    Route::post('/modules/upload', [\App\Http\Controllers\Admin\ModuleController::class, 'upload'])->name('modules.upload');

    // Cache Management Routes
    Route::post('/cache/clear', [\App\Http\Controllers\Admin\CacheController::class, 'clearAll'])->name('cache.clear');
    Route::post('/cache/clear/{type}', [\App\Http\Controllers\Admin\CacheController::class, 'clearType'])->name('cache.clear-type');
    Route::get('/cache/stats', [\App\Http\Controllers\Admin\CacheController::class, 'stats'])->name('cache.stats');

    // Code Editor Route
    Route::get('/code-editor', \App\Livewire\Admin\CodeEditor\FileEditor::class)->name('code-editor');

    // Dynamic Template Entries Routes (must be last to not conflict with other routes)
    Route::get('/{templateSlug}', \App\Livewire\Admin\TemplateEntries\EntryList::class)->name('template-entries.index');
    Route::get('/{templateSlug}/create', \App\Livewire\Admin\TemplateEntries\EntryForm::class)->name('template-entries.create');
    Route::get('/{templateSlug}/{entryId}/edit', \App\Livewire\Admin\TemplateEntries\EntryForm::class)->name('template-entries.edit');
});

// Auth routes
require __DIR__.'/auth.php';

// Template index routes (e.g., /services, /blog)
Route::match(['get', 'post'], '/{templateSlug}', [FrontendController::class, 'handleTemplateIndex'])
    ->where('templateSlug', '[a-z0-9\-]+')
    ->name('template.index');

// Catch-all route for dynamic content (must be last)
Route::match(['get', 'post'], '/{path}', [FrontendController::class, 'handleDynamicRoute'])
    ->where('path', '.*')
    ->name('dynamic');
