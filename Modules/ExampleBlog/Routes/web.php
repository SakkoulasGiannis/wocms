<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::prefix('blog')->name('blog.')->group(function () {
        // Posts
        Route::get('/posts', function () {
            return view('exampleblog::posts.index');
        })->name('posts.index');

        Route::get('/posts/create', function () {
            return view('exampleblog::posts.create');
        })->name('posts.create');

        Route::get('/posts/{id}/edit', function ($id) {
            return view('exampleblog::posts.edit', compact('id'));
        })->name('posts.edit');

        // Categories
        Route::get('/categories', function () {
            return view('exampleblog::categories.index');
        })->name('categories.index');

        Route::get('/categories/create', function () {
            return view('exampleblog::categories.create');
        })->name('categories.create');

        Route::get('/categories/{id}/edit', function ($id) {
            return view('exampleblog::categories.edit', compact('id'));
        })->name('categories.edit');

        // Tags
        Route::get('/tags', function () {
            return view('exampleblog::tags.index');
        })->name('tags.index');

        Route::get('/tags/create', function () {
            return view('exampleblog::tags.create');
        })->name('tags.create');

        Route::get('/tags/{id}/edit', function ($id) {
            return view('exampleblog::tags.edit', compact('id'));
        })->name('tags.edit');
    });
});
