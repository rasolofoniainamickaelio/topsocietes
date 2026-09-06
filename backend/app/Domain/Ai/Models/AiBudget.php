<?php

declare(strict_types=1);

namespace App\Domain\Ai\Models;

use App\Domain\Geo\Models\Country;
use Database\Factories\AiBudgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plafond de dépense IA mensuel par pays (Phase 10, "gestion des coûts...
 * quotas"). Une ligne absente pour un (pays, mois) donné signifie "pas de
 * plafond configuré", jamais "budget nul" — voir `CheckAiBudgetAction`.
 */
class AiBudget extends Model
{
    /** @use HasFactory<AiBudgetFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'period',
        'max_cost_cents',
        'spent_cents',
    ];

    protected function casts(): array
    {
        return [
            'max_cost_cents' => 'integer',
            'spent_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
