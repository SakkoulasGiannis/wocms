<?php

use Illuminate\Support\Facades\Route;
use Modules\Maps\Livewire\MapForm;
use Modules\Maps\Livewire\MapList;

Route::get('/maps', MapList::class)->name('maps.index');
Route::get('/maps/create', MapForm::class)->name('maps.create');
Route::get('/maps/{mapId}/edit', MapForm::class)->name('maps.edit');
