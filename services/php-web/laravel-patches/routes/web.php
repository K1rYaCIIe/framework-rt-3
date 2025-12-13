<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CmsApiController;
use App\Http\Controllers\Api\TestApiController;

Route::get('/', fn() => redirect('/dashboard'));
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
Route::get('/osdr', [\App\Http\Controllers\OsdrController::class, 'index']);
Route::get('/iss', [\App\Http\Controllers\IssController::class, 'index']);
Route::get('/page/{slug}', [\App\Http\Controllers\CmsController::class, 'page']);

Route::get('/api/iss/last', [\App\Http\Controllers\ProxyController::class, 'last']);
Route::get('/api/iss/trend', [\App\Http\Controllers\ProxyController::class, 'trend']);
Route::get('/api/jwst/feed', [\App\Http\Controllers\DashboardController::class, 'jwstFeed']);
Route::get('/api/astro/events', [\App\Http\Controllers\AstroController::class, "events"]);
Route::get('/api/cms/{slug}', [CmsApiController::class, 'getBlock']);

Route::get('/api/test', [TestApiController::class, 'test']);
Route::get('/api/test/params', [TestApiController::class, 'testParams']);
Route::post('/api/test/post', [TestApiController::class, 'testParams']);
