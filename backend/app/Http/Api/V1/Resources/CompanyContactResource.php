<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Domain\Company\Models\CompanyContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * N'est jamais instanciée pour un contact dont `visibility` n'autorise pas
 * l'affichage — le filtrage a lieu en amont (ShowCompanyAction), jamais ici.
 *
 * @mixin CompanyContact
 */
class CompanyContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
        ];
    }
}
