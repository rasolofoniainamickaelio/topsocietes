<?php

declare(strict_types=1);

use App\Http\Api\V1\Controllers\CountryController;
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
});
