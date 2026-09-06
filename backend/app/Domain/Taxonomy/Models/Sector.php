<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Models;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Models\ActivityContent;
use App\Domain\Content\Models\CityActivityContent;
use App\Domain\Content\Models\Fact;
use Database\Factories\SectorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sector extends Model
{
    /** @use HasFactory<SectorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'companies_count',
        'counts_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'companies_count' => 'integer',
            'counts_updated_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Activity, $this> */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_sector');
    }

    /** @return HasMany<ActivityContent, $this> */
    public function contents(): HasMany
    {
        return $this->hasMany(ActivityContent::class);
    }

    /** @return HasMany<CityActivityContent, $this> */
    public function cityActivityContents(): HasMany
    {
        return $this->hasMany(CityActivityContent::class);
    }

    /**
     * Toujours vide tant qu'aucune collecte de faits sectoriels n'existe
     * (Phase 09 ne couvre que les communes) — présente dès maintenant pour
     * que le pipeline IA (Phase 10) refuse proprement une génération par
     * secteur faute de faits, plutôt que de ne jamais pouvoir la tenter.
     *
     * @return MorphMany<Fact, $this>
     */
    public function facts(): MorphMany
    {
        return $this->morphMany(Fact::class, 'subject');
    }

    /** @return MorphMany<AiGenerationJob, $this> */
    public function generationJobs(): MorphMany
    {
        return $this->morphMany(AiGenerationJob::class, 'target');
    }
}
