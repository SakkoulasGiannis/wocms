<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

// Frontend routes
Route::get('/', [FrontendController::class, 'home'])->name('home');

// Blog routes — taxonomy filter routes come BEFORE the generic {slug} route
// so /blog/category/x and /blog/tag/x don't get swallowed by show().
Route::get('/blog', [\App\Http\Controllers\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [\App\Http\Controllers\BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [\App\Http\Controllers\BlogController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{slug}', [\App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');

// Properties routes
Route::get('/properties', [FrontendController::class, 'properties'])->name('properties.index');
Route::get('/properties/{slug}', [FrontendController::class, 'propertyShow'])->name('properties.show');

// Rental Properties routes
Route::get('/rental-properties', [FrontendController::class, 'rentalProperties'])->name('rental-properties.index');
Route::get('/rental-properties/{slug}/calendar', [FrontendController::class, 'rentalPropertyCalendar'])->name('rental-properties.calendar');
Route::get('/rental-properties/{slug}/quote', [FrontendController::class, 'rentalPropertyQuote'])->name('rental-properties.quote');
Route::post('/rental-properties/{slug}/book', [FrontendController::class, 'rentalPropertyBook'])->name('rental-properties.book');
Route::post('/hostaway/webhook', \Modules\RentalProperties\Http\Controllers\HostawayWebhookController::class)->name('hostaway.webhook');

// Booking checkout + payment (provider-agnostic; payment providers not yet implemented)
Route::post('/rental-properties/{slug}/checkout', [\Modules\RentalProperties\Http\Controllers\BookingCheckoutController::class, 'start'])->name('booking.checkout.start');
Route::get('/booking/{booking}', [\Modules\RentalProperties\Http\Controllers\BookingCheckoutController::class, 'show'])->name('booking.show');
Route::get('/booking/{booking}/return', [\Modules\RentalProperties\Http\Controllers\BookingCheckoutController::class, 'paymentReturn'])->name('booking.return');
Route::post('/payments/{provider}/webhook', [\Modules\RentalProperties\Http\Controllers\BookingCheckoutController::class, 'webhook'])->name('payments.webhook');
Route::get('/rental-properties/{slug}', [FrontendController::class, 'rentalPropertyShow'])->name('rental-properties.show');

// Contact page
Route::get('/contact', [FrontendController::class, 'contact'])->name('contact');

// Our Staff page
Route::get('/our-staff', [FrontendController::class, 'staff'])->name('our-staff');
// CSRF token refresh route
Route::get('/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
    ]);
})->name('csrf-token');

// Silences harmless 404s emitted by browsers when parsing
// <style type="text/tailwindcss">@import "tailwindcss";</style> in theme layouts.
// Returns empty CSS since the Tailwind CDN browser script handles the real import.
// Catches both /tailwindcss AND /any/nested/path/tailwindcss (relative resolution
// from nested page URLs like /contact, /properties/123 etc.).
$tailwindcssNoop = function () {
    return response('/* handled by @tailwindcss/browser CDN */', 200)
        ->header('Content-Type', 'text/css')
        ->header('Cache-Control', 'public, max-age=86400');
};
Route::get('/tailwindcss', $tailwindcssNoop);
Route::get('/{any}/tailwindcss', $tailwindcssNoop)->where('any', '.*');

// Admin routes (protected by auth middleware)
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // EditorJS upload endpoints
    Route::post('/editorjs/upload-image', [\App\Http\Controllers\Admin\EditorJsController::class, 'uploadImage'])->name('editorjs.upload-image');
    Route::post('/editorjs/fetch-image', [\App\Http\Controllers\Admin\EditorJsController::class, 'fetchImageByUrl'])->name('editorjs.fetch-image');
    Route::post('/editorjs/upload-file', [\App\Http\Controllers\Admin\EditorJsController::class, 'uploadFile'])->name('editorjs.upload-file');
    Route::get('/editorjs/media', [\App\Http\Controllers\Admin\EditorJsController::class, 'mediaList'])->name('editorjs.media');

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

    // AI Page Builder (build / edit pages via AI)
    Route::get('/ai-page-builder', \App\Livewire\Admin\AiPageBuilder\AiPageBuilderPage::class)->name('ai-page-builder');

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

    // Menu Manager Route
    Route::get('/menus', \App\Livewire\Admin\Menus\MenuManager::class)->name('menus.index');

    // Agents (Our Staff) Routes
    Route::get('/agents', \App\Livewire\Admin\Agents\AgentList::class)->name('agents.index');
    Route::get('/agents/create', \App\Livewire\Admin\Agents\AgentForm::class)->name('agents.create');
    Route::get('/agents/{agentId}/edit', \App\Livewire\Admin\Agents\AgentForm::class)->name('agents.edit');

    // Blog taxonomies (categories + tags)
    Route::get('/blog/categories', \App\Livewire\Admin\Blog\CategoryList::class)->name('blog.categories.index');
    Route::get('/blog/categories/create', \App\Livewire\Admin\Blog\CategoryForm::class)->name('blog.categories.create');
    Route::get('/blog/categories/{categoryId}/edit', \App\Livewire\Admin\Blog\CategoryForm::class)->name('blog.categories.edit');
    Route::get('/blog/tags', \App\Livewire\Admin\Blog\TagList::class)->name('blog.tags.index');

    // PageCompiler + AI Page Builder API (JSON in/out — kept under /admin so it shares auth)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/pages/{id}/export', [\App\Http\Controllers\Admin\PageCompilerController::class, 'export'])->name('pages.export');
        Route::get('/templates/skeletons', [\App\Http\Controllers\Admin\PageCompilerController::class, 'allSkeletons'])->name('templates.skeletons');
        Route::get('/templates/{slug}/skeleton', [\App\Http\Controllers\Admin\PageCompilerController::class, 'templateSkeleton'])->name('templates.skeleton');
        Route::post('/pages/compile', [\App\Http\Controllers\Admin\PageCompilerController::class, 'compile'])->name('pages.compile');
        Route::post('/pages/ai/create', [\App\Http\Controllers\Admin\PageCompilerController::class, 'aiCreate'])->name('pages.ai.create');
        Route::post('/pages/ai/edit', [\App\Http\Controllers\Admin\PageCompilerController::class, 'aiEdit'])->name('pages.ai.edit');
        Route::get('/pages/{pageId}/revisions', [\App\Http\Controllers\Admin\PageCompilerController::class, 'revisions'])->name('pages.revisions');
        Route::post('/pages/revisions/{revisionId}/restore', [\App\Http\Controllers\Admin\PageCompilerController::class, 'restore'])->name('pages.revisions.restore');
    });

    // Auto-load module admin routes (must be before the wildcard {templateSlug} catch-all)
    foreach (\Nwidart\Modules\Facades\Module::allEnabled() as $module) {
        $adminRoutes = $module->getPath().'/routes/admin.php';
        if (file_exists($adminRoutes)) {
            require $adminRoutes;
        }
    }

    // Dynamic Template Entries Routes (must be last to not conflict with other routes)
    Route::get('/{templateSlug}', \App\Livewire\Admin\TemplateEntries\EntryList::class)->name('template-entries.index');
    Route::get('/{templateSlug}/create', \App\Livewire\Admin\TemplateEntries\EntryForm::class)->name('template-entries.create');
    Route::get('/{templateSlug}/{entryId}/edit', \App\Livewire\Admin\TemplateEntries\EntryForm::class)->name('template-entries.edit');
});

// Auth routes
require __DIR__.'/auth.php';

// Auto-load module frontend routes (must be before catch-all wildcards)
foreach (\Nwidart\Modules\Facades\Module::allEnabled() as $module) {
    $frontRoutes = $module->getPath().'/routes/front.php';
    if (file_exists($frontRoutes)) {
        require $frontRoutes;
    }
}

// Template index routes (e.g., /services, /blog)
Route::match(['get', 'post'], '/{templateSlug}', [FrontendController::class, 'handleTemplateIndex'])
    ->where('templateSlug', '[a-z0-9\-]+')
    ->name('template.index');

// Catch-all route for dynamic content (must be last)
Route::match(['get', 'post'], '/{path}', [FrontendController::class, 'handleDynamicRoute'])
    ->where('path', '.*')
    ->name('dynamic');
