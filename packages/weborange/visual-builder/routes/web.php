<?php

use Illuminate\Support\Facades\Route;
use Weborange\VisualBuilder\Http\Controllers\AssetController;
use Weborange\VisualBuilder\Http\Controllers\BuilderController;

Route::get('/', [BuilderController::class, 'index'])->name('index');
Route::post('/save', [BuilderController::class, 'save'])->name('save');
Route::get('/sections', [BuilderController::class, 'sections'])->name('sections');
Route::get('/tokens', [BuilderController::class, 'tokens'])->name('tokens');

// Static JS engine, served from the package (no publish step).
Route::get('/asset/{file}', [AssetController::class, 'js'])
    ->where('file', '.*\.js')
    ->name('asset');
