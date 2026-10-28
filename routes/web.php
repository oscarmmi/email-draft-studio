<?php

use App\Http\Controllers\DraftController;
use App\Http\Controllers\StudioController;
use Illuminate\Support\Facades\Route;

Route::get('/', StudioController::class)->name('home');
Route::post('/drafts', [DraftController::class, 'store'])->name('drafts.store');
