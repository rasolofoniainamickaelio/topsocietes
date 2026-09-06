<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Moderation\Models\DisputeEvent;
use App\Domain\Moderation\Models\DisputeReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Suivi public (Phase 22) : jamais `internal_note`, `ip_hash`,
 * `assigned_to`, ni les coordonnées du demandeur — n'importe qui connaissant
 * le numéro de suivi peut interroger ce point, sans authentification.
 *
 * @mixin DisputeReport
 */
class DisputeReportTrackResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tracking_number' => $this->id,
            'field' => $this->field,
            'current_value' => $this->current_value,
            'proposed_value' => $this->proposed_value,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'resolved_at' => $this->resolved_at,
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn (DisputeEvent $event) => [
                'action' => $event->action,
                'created_at' => $event->created_at,
            ])->all()),
        ];
    }
}
