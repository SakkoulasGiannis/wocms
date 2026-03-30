<?php

use Illuminate\Support\Facades\Route;
use Modules\Properties\Livewire\PropertyForm;
use Modules\Properties\Livewire\PropertyList;

/*
|--------------------------------------------------------------------------
| Properties Admin Routes
|--------------------------------------------------------------------------
| Loaded inside the admin prefix + name('admin.') + auth middleware group
| automatically via routes/web.php module auto-loader.
|
*/

Route::get('/properties', PropertyList::class)->name('properties.index');
Route::get('/properties/create', PropertyForm::class)->name('properties.create');
Route::get('/properties/{propertyId}/edit', PropertyForm::class)->name('properties.edit');

Route::get('/crm-sync', \Modules\Properties\Livewire\CrmSyncDashboard::class)->name('crm-sync');
