<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'overview'])->name('dashboard.overview');
Route::get('/campaigns', [DashboardController::class, 'campaigns'])->name('dashboard.campaigns');
Route::get('/keywords', [DashboardController::class, 'keywords'])->name('dashboard.keywords');
Route::get('/budget', [DashboardController::class, 'budget'])->name('dashboard.budget');
Route::get('/alerts', [DashboardController::class, 'alerts'])->name('dashboard.alerts');
Route::get('/notes', [DashboardController::class, 'notes'])->name('dashboard.notes');

// Google Ads OAuth
Route::get('/google/connect', [GoogleAuthController::class, 'redirect'])->name('google.connect');
Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
Route::get('/google/status', [GoogleAuthController::class, 'status'])->name('google.status');
