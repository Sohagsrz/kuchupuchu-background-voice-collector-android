<?php

use App\Http\Controllers\VoiceStudioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('studio.index');
});

Route::get('/studio', [VoiceStudioController::class, 'index'])->name('studio.index');
Route::get('/recordings', [VoiceStudioController::class, 'index']);
