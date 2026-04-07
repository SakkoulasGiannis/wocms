<?php

use Illuminate\Support\Facades\Route;
use Modules\RentalProperties\Livewire\RentalPropertyForm;
use Modules\RentalProperties\Livewire\RentalPropertyList;

/*
|--------------------------------------------------------------------------
| RentalProperties Admin Routes
|--------------------------------------------------------------------------
| Loaded inside the admin prefix + name('admin.') + auth middleware group
| automatically via routes/web.php module auto-loader.
|
*/

Route::get('/rental-properties', RentalPropertyList::class)->name('rentals.index');
Route::get('/rental-properties/create', RentalPropertyForm::class)->name('rentals.create');
Route::get('/rental-properties/{propertyId}/edit', RentalPropertyForm::class)->name('rentals.edit');
