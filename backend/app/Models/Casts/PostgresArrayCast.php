<?php

declare(strict_types=1);

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * (Dé)sérialise une colonne Postgres native `varchar[]` (ex. `{75001,75002}`)
 * vers/depuis un tableau PHP. Suffisant pour des valeurs simples (codes
 * postaux) : ne vise pas à gérer du texte libre arbitraire.
 *
 * @implements CastsAttributes<array<int, string>|null, array<int, string>|null>
 */
class PostgresArrayCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value, '{}');

        if ($trimmed === '') {
            return [];
        }

        return str_getcsv($trimmed, ',', '"');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $escaped = array_map(
            static fn (string $item): string => '"'.str_replace('"', '\\"', $item).'"',
            $value,
        );

        return '{'.implode(',', $escaped).'}';
    }
}
