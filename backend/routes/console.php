<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sauvegardes automatiques (BDD + fichiers) — un seul cron système requis
// sur le VPS : `* * * * * php artisan schedule:run` (voir docs/deployment.md).
// Nettoyage avant la nouvelle sauvegarde pour libérer de l'espace, puis
// contrôle de fraîcheur/taille en dernier.
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('02:00');

// Collecte de faits sourcés (Phase 09 — Wikipedia/Wikidata/OSM) pour
// chaque pays actif. Piloté par la table `countries` (CLAUDE.md §6.6),
// jamais un pays codé en dur. Cadence hebdomadaire provisoire : les faits
// territoriaux changent rarement, à ajuster une fois le volume réel de
// villes avec identifiants externes renseignés connu.
Schedule::call(function (): void {
    Country::query()->where('is_active', true)->pluck('subdomain')->each(
        fn (string $subdomain) => Artisan::call('sources:collect-cities', ['country' => $subdomain]),
    );
})->weekly()->sundays()->at('03:00')->name('sources:collect-cities:all-countries');
