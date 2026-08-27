<?php

declare(strict_types=1);

use App\Http\Api\V1\Controllers\ActivityIndexController;
use App\Http\Api\V1\Controllers\CityIndexController;
use App\Http\Api\V1\Controllers\CityShowController;
use App\Http\Api\V1\Controllers\CompanyIndexController;
use App\Http\Api\V1\Controllers\CompanyShowController;
use App\Http\Api\V1\Controllers\CountryController;
use App\Http\Api\V1\Controllers\DisputeReportStoreController;
use App\Http\Api\V1\Controllers\SectorIndexController;
use App\Http\Middleware\ResolveCountry;
use Illuminate\Support\Facades\Route;

/**
 * `{country}` = `countries.subdomain` (ex. "fr"), résolu explicitement en
 * paramètre plutôt que par sous-domaine réel : l'API reste sur une origine
 * unique, c'est le frontend qui transmet le pays déduit de son propre
 * sous-domaine (voir docs/DATABASE.md et le plan multi-pays).
 */
Route::prefix('v1/{country}')->middleware(ResolveCountry::class)->group(function (): void {
    Route::get('/', CountryController::class);
    Route::get('/cities', CityIndexController::class);
    Route::get('/cities/{slug}', CityShowController::class);
    Route::get('/activities', ActivityIndexController::class);
    Route::get('/sectors', SectorIndexController::class);
    Route::get('/companies', CompanyIndexController::class);
    Route::get('/companies/{slug}', CompanyShowController::class);
    Route::post('/companies/{slug}/disputes', DisputeReportStoreController::class)->middleware('throttle:5,1');
});
