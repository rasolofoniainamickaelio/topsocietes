<?php

declare(strict_types=1);

namespace App\Domain\Content\Data;

use Spatie\LaravelData\Data;

/**
 * Fait extrait d'un `CollectedDocumentData` par un connecteur (Phase 09).
 * Ne porte que ce que le connecteur détermine — sujet, source et dates
 * sont ajoutés par l'Action orchestratrice au moment de la persistance.
 */
class FactData extends Data
{
    /**
     * @param  array<string, mixed>|null  $valueJson
     */
    public function __construct(
        public readonly string $key,
        public readonly ?string $value,
        public readonly ?array $valueJson = null,
    ) {}
}
