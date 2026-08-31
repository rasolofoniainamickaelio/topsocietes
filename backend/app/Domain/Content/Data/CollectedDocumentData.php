<?php

declare(strict_types=1);

namespace App\Domain\Content\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * Résultat brut+normalisé d'un connecteur (Phase 09) — miroir des colonnes
 * de `source_documents`, avant persistance.
 */
class CollectedDocumentData extends Data
{
    /**
     * @param  array<string, mixed>  $rawPayload
     * @param  array<string, mixed>  $normalizedPayload
     */
    public function __construct(
        public readonly string $url,
        public readonly array $rawPayload,
        public readonly array $normalizedPayload,
        public readonly int $httpStatus,
        public readonly CarbonImmutable $fetchedAt,
    ) {}
}
