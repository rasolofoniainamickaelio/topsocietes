<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Actions\TransformContentAction;
use App\Domain\Ai\Enums\GenerationMode;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Models\AdminDivisionContent;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Content\Models\CityContent;
use App\Domain\Content\Models\DistrictContent;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class TransformContentCommand extends Command
{
    protected $signature = 'ai:transform-content
        {type : city|district|admin-division|activity|city-activity}
        {id : Identifiant de la ligne de contenu}
        {mode : rewrite|summarize|contextualize}';

    protected $description = 'Réécrit, résume ou contextualise un contenu déjà existant (jamais publié automatiquement)';

    public function handle(TransformContentAction $action): int
    {
        $model = match ($this->argument('type')) {
            'city' => CityContent::class,
            'district' => DistrictContent::class,
            'admin-division' => AdminDivisionContent::class,
            'activity' => ActivityContent::class,
            'city-activity' => CityActivityContent::class,
            default => null,
        };

        if ($model === null) {
            $this->error('Type invalide (attendu : city, district, admin-division, activity, city-activity).');

            return self::FAILURE;
        }

        /** @var Model|null $content */
        $content = $model::query()->find($this->argument('id'));

        if ($content === null) {
            $this->error("Ligne de contenu #{$this->argument('id')} introuvable pour ce type.");

            return self::FAILURE;
        }

        $mode = GenerationMode::tryFrom((string) $this->argument('mode'));

        if ($mode === null || $mode === GenerationMode::Create) {
            $this->error('Mode invalide (attendu : rewrite, summarize, contextualize).');

            return self::FAILURE;
        }

        $job = $action->execute($content, $mode);

        $this->info("Transformation terminée : statut {$job->status->value}.");

        if ($job->error_message !== null) {
            $this->line("  {$job->error_message}");
        }

        return self::SUCCESS;
    }
}
