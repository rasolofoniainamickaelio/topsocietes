<?php

declare(strict_types=1);

use App\Http\Api\V1\Controllers\ActivityIndexController;
use App\Http\Api\V1\Controllers\ActivityShowController;
use App\Http\Api\V1\Controllers\AdminDivisionIndexController;
use App\Http\Api\V1\Controllers\AdminDivisionShowController;
use App\Http\Api\V1\Controllers\AdSlotCampaignsController;
use App\Http\Api\V1\Controllers\Auth\LoginController;
use App\Http\Api\V1\Controllers\Auth\LogoutController;
use App\Http\Api\V1\Controllers\Auth\MeController;
use App\Http\Api\V1\Controllers\Auth\MyCompaniesController;
use App\Http\Api\V1\Controllers\Auth\RegisterController;
use App\Http\Api\V1\Controllers\CityIndexController;
use App\Http\Api\V1\Controllers\CityShowController;
use App\Http\Api\V1\Controllers\CompanyAutocompleteController;
use App\Http\Api\V1\Controllers\CompanyCheckoutController;
use App\Http\Api\V1\Controllers\CompanyClaimStoreController;
use App\Http\Api\V1\Controllers\CompanyIndexController;
use App\Http\Api\V1\Controllers\CompanySearchController;
use App\Http\Api\V1\Controllers\CompanyShowController;
use App\Http\Api\V1\Controllers\CountryController;
use App\Http\Api\V1\Controllers\DisputeReportStoreController;
use App\Http\Api\V1\Controllers\DistrictShowController;
use App\Http\Api\V1\Controllers\PlanIndexController;
use App\Http\Api\V1\Controllers\ResolvePathController;
use App\Http\Api\V1\Controllers\RobotsController;
use App\Http\Api\V1\Controllers\SectorIndexController;
use App\Http\Api\V1\Controllers\SectorShowController;
use App\Http\Api\V1\Controllers\ServiceLinkIndexController;
use App\Http\Api\V1\Controllers\SitemapIndexController;
use App\Http\Api\V1\Controllers\SitemapShardController;
use App\Http\Api\V1\Controllers\StripeWebhookController;
use App\Http\Middleware\ResolveCountry;
use Illuminate\Support\Facades\Route;

/**
 * Hors préfixe `v1/{country}` : un compte utilisateur n'est rattaché à
 * aucun pays. Sanctum SPA (cookies de session) — voir
 * docs/adr/0004-billing-auth.md.
 */
Route::prefix('v1/auth')->group(function (): void {
    Route::post('/register', RegisterController::class)->middleware('throttle:5,1');
    Route::post('/login', LoginController::class)->middleware('throttle:5,1');
    Route::post('/logout', LogoutController::class)->middleware('auth:sanctum');
    Route::get('/me', MeController::class)->middleware('auth:sanctum');
    // Tableau de bord entreprise (Phase 07) : un compte n'étant rattaché à
    // aucun pays (voir docstring au-dessus), les fiches revendiquées d'un
    // utilisateur peuvent appartenir à plusieurs pays — hors du préfixe
    // `v1/{country}`, comme le reste de ce groupe.
    Route::get('/me/companies', MyCompaniesController::class)->middleware('auth:sanctum');
});

/**
 * Signature vérifiée dans le contrôleur, jamais par une session/CSRF — voir
 * `bootstrap/app.php` (exception CSRF sur ce chemin).
 */
Route::post('/webhooks/stripe', StripeWebhookController::class);

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
    Route::get('/activities/{slug}', ActivityShowController::class);
    Route::get('/sectors', SectorIndexController::class);
    Route::get('/sectors/{slug}', SectorShowController::class);
    Route::get('/districts/{slug}', DistrictShowController::class);
    Route::get('/admin-divisions', AdminDivisionIndexController::class);
    Route::get('/admin-divisions/{slug}', AdminDivisionShowController::class);
    Route::get('/search', CompanySearchController::class);
    Route::get('/search/autocomplete', CompanyAutocompleteController::class);
    Route::get('/companies', CompanyIndexController::class);
    Route::get('/companies/{slug}', CompanyShowController::class);
    Route::post('/companies/{slug}/disputes', DisputeReportStoreController::class)->middleware('throttle:5,1');
    Route::get('/ad-slots/{code}/campaigns', AdSlotCampaignsController::class);
    Route::get('/service-links', ServiceLinkIndexController::class);
    Route::get('/resolve', ResolvePathController::class);
    Route::get('/plans', PlanIndexController::class);
    Route::post('/companies/{slug}/claims', CompanyClaimStoreController::class)->middleware('auth:sanctum');
    Route::post('/companies/{slug}/checkout', CompanyCheckoutController::class)->middleware('auth:sanctum');

    // Fichiers de service SEO (Phase 17-18) : le frontend, servi sur le vrai
    // sous-domaine pays, expose `/sitemap.xml` et `/robots.txt` en proxy
    // direct vers ces mêmes chemins (l'API reste sur une origine unique,
    // voir docstring plus haut).
    Route::get('/sitemap.xml', SitemapIndexController::class);
    Route::get('/sitemaps/{type}-{index}.xml', SitemapShardController::class)->where(['type' => '[a-z_]+', 'index' => '[0-9]+']);
    Route::get('/robots.txt', RobotsController::class);
});
