<?php

declare(strict_types=1);

namespace App\Domain\Ai\Models;

use Database\Factories\AiGenerationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal d'audit immuable d'une génération : `input_payload` est le JSON
 * de faits exact envoyé au LLM, `validation_report` le résultat du
 * validateur anti-hallucination (doublons, sections rejetées, faits non
 * sourcés). Pas de colonne `updated_at` : un log ne se modifie pas.
 */
class AiGenerationLog extends Model
{
    /** @use HasFactory<AiGenerationLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'generation_job_id',
        'input_payload',
        'raw_output',
        'validation_report',
    ];

    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'validation_report' => 'array',
        ];
    }

    /** @return BelongsTo<AiGenerationJob, $this> */
    public function generationJob(): BelongsTo
    {
        return $this->belongsTo(AiGenerationJob::class);
    }
}
