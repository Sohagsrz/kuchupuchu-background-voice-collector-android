<?php

use App\Http\Controllers\Api\RecordingApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', [RecordingApiController::class, 'health']);
    Route::post('/recordings', [RecordingApiController::class, 'upload']);
});
