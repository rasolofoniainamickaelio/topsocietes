<?php

declare(strict_types=1);

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
