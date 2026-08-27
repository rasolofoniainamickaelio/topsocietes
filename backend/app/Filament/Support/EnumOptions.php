<?php

declare(strict_types=1);

namespace App\Filament\Support;

/**
 * Convertit un enum PHP natif (backé par une chaîne) en options Select.
 * Les enums du Domain n'implémentent jamais d'interface Filament
 * (CLAUDE.md §2 : Domain ne dépend jamais de Filament) — cette conversion
 * vit donc côté Filament, jamais dans app/Domain.
 */
class EnumOptions
{
    /**
     * @param  class-string<\BackedEnum>  $enumClass
     * @return array<string, string>
     */
    public static function for(string $enumClass): array
    {
        return collect($enumClass::cases())
            ->mapWithKeys(fn ($case) => [$case->value => str($case->name)->headline()->toString()])
            ->all();
    }
}
