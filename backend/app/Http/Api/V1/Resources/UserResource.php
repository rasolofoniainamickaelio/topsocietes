<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Jamais `password` ni `remember_token` — uniquement ce dont le frontend a
 * besoin pour son état d'authentification.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roles->pluck('name'),
        ];
    }
}
