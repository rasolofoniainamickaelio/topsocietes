<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Billing\Contracts\CheckoutGatewayInterface;
use App\Domain\Billing\Services\StripeCheckoutGateway;
use App\Domain\Company\Models\Company;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\DistrictContent;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\District;
use App\Domain\Geo\Models\PointOfInterest;
use App\Domain\Taxonomy\Models\Activity;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Isole le Domain du prestataire de paiement (même patron que
        // SearchEngineInterface, ADR 0001) — voir docs/adr/0004-billing-auth.md.
        $this->app->singleton(StripeClient::class, fn (): StripeClient => new StripeClient(
            (string) config('services.stripe.secret'),
        ));

        $this->app->bind(CheckoutGatewayInterface::class, StripeCheckoutGateway::class);
    }

    public function boot(): void
    {
        // Les factories restent à plat dans database/factories/ (jamais
        // sous app/Domain/, cf. CLAUDE.md §2) alors que les modèles vivent
        // sous App\Domain\{Contexte}\Models\ : le résolveur par défaut de
        // Laravel ne sait mapper que App\Models\X vers Database\Factories\X,
        // il faut donc l'étendre pour ignorer le sous-namespace du modèle.
        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // super_admin contourne toute vérification de permission — voir
        // docs/adr/0002-access-control.md. Aucune permission ne lui est
        // assignée en base pour éviter toute désynchronisation à l'ajout
        // d'une nouvelle permission.
        Gate::before(fn (User $user): ?true => $user->hasRole(RoleName::SuperAdmin->value) ? true : null);

        // Doc OpenAPI interactive (Scramble) : jamais en production, ouverte
        // sur tout autre environnement (local déjà autorisé nativement par
        // RestrictedDocsAccess, staging/autres via ce Gate).
        Gate::define('viewApiDocs', fn (): bool => ! app()->environment('production'));

        // Alias courts plutôt que les FQCN dans les colonnes polymorphiques
        // (source_documents.subject_type, facts.subject_type,
        // content_source_links.content_type) : ne pas coupler le contenu de
        // la base à la structure de namespace de l'application.
        Relation::enforceMorphMap([
            'user' => User::class,
            'city' => City::class,
            'district' => District::class,
            'activity' => Activity::class,
            'poi' => PointOfInterest::class,
            'company' => Company::class,
            'city_content' => CityContent::class,
            'district_content' => DistrictContent::class,
            'activity_content' => ActivityContent::class,
            'city_activity_content' => CityActivityContent::class,
        ]);
    }
}
