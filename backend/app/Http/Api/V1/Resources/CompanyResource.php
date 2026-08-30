<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Company\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `contacts` n'apparaît dans le tableau que si au moins un contact est
 * démasqué — jamais comme clé présente à `null`/`[]` (CLAUDE.md §6.3 : la
 * clé elle-même doit être absente, pas seulement vide, pour qu'aucun bug de
 * sérialisation ne puisse un jour la remplir par erreur).
 *
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'national_id' => $this->national_id,
            'legal_name' => $this->legal_name,
            'trade_name' => $this->trade_name,
            'legal_form_code' => $this->legal_form_code,
            'legal_form_label' => $this->legal_form_label,
            'status' => $this->status,
            'created_date' => $this->created_date,
            'headcount_range' => $this->headcount_range,
            'about_text' => $this->about_text,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            // `activity_id`/`city_id` sont nullables (une entreprise
            // fraîchement importée peut n'avoir ni l'un ni l'autre résolu,
            // Phase 03/04) : `whenLoaded` invoque son callback dès que la
            // relation est chargée, même si sa valeur est `null` — d'où la
            // garde explicite, sur le même modèle que `district` ci-dessous.
            'activity' => $this->whenLoaded('activity', fn () => $this->activity === null ? null : [
                'slug' => $this->activity->slug,
                'label' => $this->activity->public_label,
                'sectors' => $this->activity->relationLoaded('sectors')
                    ? $this->activity->sectors->map(fn ($sector) => [
                        'slug' => $sector->slug,
                        'name' => $sector->name,
                    ])->all()
                    : [],
            ]),
            'city' => $this->whenLoaded('city', fn () => $this->city === null ? null : [
                'slug' => $this->city->slug,
                'name' => $this->city->name,
            ]),
            'district' => $this->whenLoaded('district', fn () => $this->district === null ? null : [
                'slug' => $this->district->slug,
                'name' => $this->district->name,
            ]),
            'main_establishment' => $this->whenLoaded('mainEstablishment', fn () => $this->mainEstablishment === null
                ? null
                : EstablishmentResource::make($this->mainEstablishment)),
            'nearby_pois' => CompanyNearbyPoiResource::collection($this->whenLoaded('nearbyPois')),
            ...($this->relationLoaded('contacts') && $this->contacts->isNotEmpty()
                ? ['contacts' => CompanyContactResource::collection($this->contacts)]
                : []),
        ];
    }
}
