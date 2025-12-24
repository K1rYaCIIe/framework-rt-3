<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OsdrController;
use App\Http\Controllers\IssController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\AstroController;
use App\Http\Controllers\CmsController;

Route::get('/', fn() => redirect('/dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/osdr',      [OsdrController::class,      'index']);
Route::get('/iss',       [IssController::class,       'index']);
Route::get('/telemetry', [DashboardController::class, 'telemetry']);

// Proxy to rust_iss
Route::get('/api/iss/last',  [ProxyController::class, 'last']);
Route::get('/api/iss/trend', [ProxyController::class, 'trend']);
Route::get('/api/osdr/list', [ProxyController::class, 'osdrList']);
Route::get('/api/space/summary', [ProxyController::class, 'spaceSummary']);

// JWST / Astro
Route::get('/api/jwst/feed', [DashboardController::class, 'jwstFeed']);
Route::get("/api/astro/events", [AstroController::class, "events"]);

Route::get('/page/{slug}', [CmsController::class, 'page']);
