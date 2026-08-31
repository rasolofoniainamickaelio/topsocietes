<?php

declare(strict_types=1);

namespace App\Domain\Content\Contracts;

use App\Domain\Content\Data\CollectedDocumentData;
use App\Domain\Content\Data\FactData;
use App\Domain\Content\Enums\SourceProvider;
use Illuminate\Database\Eloquent\Model;

/**
 * Une classe par source publique (Phase 09). `collect()` et
 * `extractFacts()` sont volontairement séparées : la première fait
 * l'appel HTTP (jamais d'exception qui remonte — `null` si la source est
 * indisponible ou n'a rien pour ce sujet), la seconde est une fonction
 * pure testable sans mock HTTP.
 */
interface SourceConnector
{
    public function provider(): SourceProvider;

    public function collect(Model $subject): ?CollectedDocumentData;

    /**
     * @return array<int, FactData>
     */
    public function extractFacts(CollectedDocumentData $document, Model $subject): array;
}
