<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\ImageMaps\Livewire\ImageMapForm;
use Modules\ImageMaps\Livewire\ImageMapList;
use Modules\ImageMaps\Models\ImageMap;

Route::get('/image-maps', ImageMapList::class)->name('imagemaps.index');
Route::get('/image-maps/create', ImageMapForm::class)->name('imagemaps.create');
Route::get('/image-maps/{imageMapId}/edit', ImageMapForm::class)->name('imagemaps.edit');

Route::post('/image-maps/save-shapes', function (Request $request) {
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'slug' => 'required|string|max:255',
        'active' => 'boolean',
        'shapes_json' => 'nullable|string',
        'id' => 'nullable|integer',
    ]);

    $items = json_decode($validated['shapes_json'] ?? '{}', true) ?? [];

    $imageMap = ImageMap::updateOrCreate(
        ['id' => $validated['id'] ?? null],
        [
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'active' => $validated['active'] ?? true,
            'items' => $items,
        ]
    );

    return response()->json([
        'success' => true,
        'id' => $imageMap->id,
    ]);
})->name('imagemaps.save-shapes');
