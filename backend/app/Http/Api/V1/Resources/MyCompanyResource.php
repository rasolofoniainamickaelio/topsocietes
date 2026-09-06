<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Company\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue "tableau de bord entreprise" (Phase 07) — jamais les coordonnées
 * masquées/démasquées elles-mêmes, seulement de quoi afficher la fiche et
 * son statut d'abonnement.
 *
 * @mixin Company
 */
class MyCompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subscription = $this->subscriptions->first();

        return [
            'slug' => $this->slug,
            'legal_name' => $this->legal_name,
            'country' => $this->whenLoaded('country', fn () => $this->country->subdomain),
            'subscription' => $subscription === null ? null : [
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end,
            ],
        ];
    }
}
