<?php

declare(strict_types=1);

namespace App\Domain\Import\Support;

use Carbon\Carbon;
use Throwable;

/**
 * Registre nommé de transformateurs appliqués à une valeur brute mappée,
 * référencés par leur nom dans `import_mappings.transformers`
 * (`{"national_id": "digits_only"}`). Volontairement petit : un nouveau
 * besoin de normalisation ajoute un `case` ici, jamais une classe dédiée.
 */
class RowTransformers
{
    public function apply(string $transformer, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($transformer) {
            'trim' => trim($value) ?: null,
            'upper' => mb_strtoupper(trim($value)),
            'digits_only' => preg_replace('/\D+/', '', $value) ?: null,
            'date_ymd' => $this->parseDate($value),
            default => $value,
        };
    }

    private function parseDate(string $value): ?string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
