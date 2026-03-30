<?php

use Illuminate\Support\Facades\Route;
use Modules\Slider\Livewire\SliderForm;
use Modules\Slider\Livewire\SliderList;

/*
|--------------------------------------------------------------------------
| Slider Admin Routes
|--------------------------------------------------------------------------
| Loaded inside the admin prefix + name('admin.') + auth middleware group
| automatically via routes/web.php module auto-loader.
|
*/

Route::get('/sliders', SliderList::class)->name('slider.index');
Route::get('/sliders/create', SliderForm::class)->name('slider.create');
Route::get('/sliders/{sliderId}/edit', SliderForm::class)->name('slider.edit');
