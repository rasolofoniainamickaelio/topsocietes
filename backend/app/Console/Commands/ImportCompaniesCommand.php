<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Geo\Models\Country;
use App\Domain\Import\Actions\StartImportBatchAction;
use App\Domain\Import\Enums\ImportFormat;
use App\Domain\Import\Models\ImportMapping;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Point d'entrée manuel de la Phase 03. Fine à dessein : résout ses
 * paramètres puis délègue tout le travail à `StartImportBatchAction`
 * (CLAUDE.md §3 — un contrôleur/une commande orchestre, ne décide pas).
 */
class ImportCompaniesCommand extends Command
{
    protected $signature = 'import:companies
        {path : Chemin du fichier sur le disque local (storage/app/private/...)}
        {country : Sous-domaine du pays, ex. fr}
        {--format=csv : Format du fichier (csv, json, xml)}
        {--mapping= : ID du mapping à utiliser (sinon le mapping par défaut du pays)}
        {--user= : ID de l\'utilisateur à l\'origine de l\'import, pour la journalisation}';

    protected $description = "Démarre l'import d'un fichier d'entreprises pour un pays donné";

    public function handle(StartImportBatchAction $action): int
    {
        $country = Country::query()
            ->where('subdomain', $this->argument('country'))
            ->where('is_active', true)
            ->first();

        if ($country === null) {
            $this->error("Pays actif introuvable pour le sous-domaine « {$this->argument('country')} ».");

            return self::FAILURE;
        }

        $format = ImportFormat::tryFrom((string) $this->option('format'));

        if ($format === null) {
            $this->error("Format inconnu : {$this->option('format')}.");

            return self::FAILURE;
        }

        $mapping = $this->resolveMapping($country);

        if ($mapping === null) {
            $this->error("Aucun mapping par défaut n'existe pour {$country->name} : passez --mapping=ID ou créez-en un.");

            return self::FAILURE;
        }

        $triggeredBy = $this->option('user') !== null
            ? User::query()->find($this->option('user'))
            : null;

        $batch = $action->execute($country, $this->argument('path'), $format, $mapping, $triggeredBy);

        $this->info("Lot d'import #{$batch->id} démarré : {$batch->total_rows} ligne(s) à traiter.");

        return self::SUCCESS;
    }

    private function resolveMapping(Country $country): ?ImportMapping
    {
        if ($this->option('mapping') !== null) {
            return ImportMapping::query()->find($this->option('mapping'));
        }

        return ImportMapping::query()
            ->where('country_id', $country->id)
            ->where('is_default', true)
            ->first();
    }
}
