<?php

use Illuminate\Support\Facades\Route;
use Modules\RentalProperties\Http\Controllers\RentalPropertiesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('rentalproperties', RentalPropertiesController::class)->names('rentalproperties');
});
