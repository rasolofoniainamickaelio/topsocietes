<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\Activity;
use App\Models\ActivityContent;
use App\Models\City;
use App\Models\CityActivityContent;
use App\Models\CityContent;
use App\Models\Company;
use App\Models\District;
use App\Models\DistrictContent;
use App\Models\PointOfInterest;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // super_admin contourne toute vérification de permission — voir
        // docs/adr/0002-access-control.md. Aucune permission ne lui est
        // assignée en base pour éviter toute désynchronisation à l'ajout
        // d'une nouvelle permission.
        Gate::before(fn (User $user): ?true => $user->hasRole(RoleName::SuperAdmin->value) ? true : null);

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
