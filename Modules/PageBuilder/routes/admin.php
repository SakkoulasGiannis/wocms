<?php

use Illuminate\Support\Facades\Route;
use Modules\PageBuilder\Livewire\Admin\PageSections\PageSelector;
use Modules\PageBuilder\Livewire\Admin\PageSections\SectionEditor;
use Modules\PageBuilder\Livewire\Admin\PageSections\SectionManager;
use Modules\PageBuilder\Livewire\Admin\PageSections\VisualPageEditor;
use Modules\PageBuilder\Livewire\Admin\SectionTemplates\SectionTemplateForm;
use Modules\PageBuilder\Livewire\Admin\SectionTemplates\SectionTemplateList;

/*
|--------------------------------------------------------------------------
| PageBuilder Admin Routes
|--------------------------------------------------------------------------
| Loaded inside the admin prefix + name('admin.') + auth middleware group
| automatically via routes/web.php module auto-loader.
|
*/

// Page Sections Routes
Route::get('/page-sections', PageSelector::class)->name('page-sections.index');
Route::get('/page-sections/manage/{sectionableType}/{sectionableId}', SectionManager::class)->name('page-sections');
Route::get('/page-sections/section/{sectionId}/edit', SectionEditor::class)->name('page-sections.edit');
Route::get('/page-sections/visual/{sectionableType}/{sectionableId}', VisualPageEditor::class)->name('page-sections.visual');

// Section Templates Routes
Route::get('/section-templates', SectionTemplateList::class)->name('section-templates.index');
Route::get('/section-templates/create', SectionTemplateForm::class)->name('section-templates.create');
Route::get('/section-templates/{templateId}/edit', SectionTemplateForm::class)->name('section-templates.edit');
