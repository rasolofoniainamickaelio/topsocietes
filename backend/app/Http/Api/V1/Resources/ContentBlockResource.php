<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Registre de blocs (CLAUDE.md §4) : `type` + `data`, jamais de logique
 * conditionnelle par page côté frontend. Réutilisable pour tout modèle de
 * contenu mutualisé (`CityContent`, `ActivityContent`, `CityActivityContent`,
 * `DistrictContent`) : ils partagent tous `section`/`title`/`body`/`data`.
 *
 * @property string $section
 * @property string|null $title
 * @property string|null $body
 * @property array<string, mixed>|null $data
 */
class ContentBlockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->section,
            'data' => [
                'title' => $this->title,
                'body' => $this->body,
                ...($this->data ?? []),
            ],
        ];
    }
}
