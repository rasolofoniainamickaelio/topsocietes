<?php

declare(strict_types=1);

namespace App\Domain\Ai\Models;

use App\Domain\Ai\Enums\AiProvider;
use App\Domain\Ai\Enums\GenerationMode;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Database\Factories\AiGenerationJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * `target` : le sujet pour lequel du contenu est généré (city, district,
 * activity, company — jamais poi, qui n'a pas de table de contenu propre).
 * Exécuté en batch uniquement, jamais au chargement d'une page.
 *
 * @property GenerationStatus $status
 * @property AiProvider $provider
 * @property Carbon $scheduled_at
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 */
class AiGenerationJob extends Model
{
    /** @use HasFactory<AiGenerationJobFactory> */
    use HasFactory;

    protected $fillable = [
        'target_type',
        'target_id',
        'activity_id',
        'sector_id',
        'section',
        'mode',
        'locale',
        'prompt_id',
        'model',
        'provider',
        'status',
        'attempts',
        'batch_reference',
        'scheduled_at',
        'started_at',
        'finished_at',
        'duration_ms',
        'input_tokens',
        'output_tokens',
        'cost_cents',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'status' => GenerationStatus::class,
            'mode' => GenerationMode::class,
            'attempts' => 'integer',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost_cents' => 'integer',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<AiPrompt, $this> */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(AiPrompt::class, 'prompt_id');
    }

    /**
     * Uniquement renseignée pour une génération croisée ville×activité, où
     * `target` pointe sur la ville faute de modèle Eloquent propre au
     * couple (jamais en même temps que `sector`, même XOR que
     * `city_activity_contents`).
     *
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Symétrique de `activity()` pour une génération croisée ville×secteur.
     *
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /** @return HasMany<AiGenerationLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(AiGenerationLog::class);
    }
}
