<?php

declare(strict_types=1);

namespace App\Domain\Ai\Models;

use App\Domain\Content\Enums\ContentSectionScope;
use Database\Factories\AiPromptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Prompt versionné (`unique(key, version)`, un seul actif par `key` via
 * index unique partiel). `scope` réutilise `ContentSectionScope` : un
 * prompt cible le même type de sujet que les sections qu'il génère.
 */
class AiPrompt extends Model
{
    /** @use HasFactory<AiPromptFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'version',
        'scope',
        'system_prompt',
        'user_template',
        'model',
        'parameters',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scope' => ContentSectionScope::class,
            'parameters' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<AiGenerationJob, $this> */
    public function generationJobs(): HasMany
    {
        return $this->hasMany(AiGenerationJob::class, 'prompt_id');
    }
}
