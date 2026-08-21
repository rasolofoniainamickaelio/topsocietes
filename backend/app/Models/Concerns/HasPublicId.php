<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Génère un `public_id` (ULID) stable à la création, utilisé pour les URL
 * de secours et l'API — l'identifiant interne (`id`) n'est jamais exposé
 * publiquement (CLAUDE.md §6.4 : l'identifiant public ne change jamais).
 */
trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function ($model): void {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::ulid();
            }
        });
    }
}
