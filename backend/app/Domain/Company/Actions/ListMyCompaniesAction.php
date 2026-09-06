<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Company\Enums\CompanyClaimStatus;
use App\Domain\Company\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Le tableau de bord entreprise (Phase 07) : les fiches sur lesquelles
 * l'utilisateur détient une revendication approuvée, avec leur dernier
 * abonnement pour afficher le statut sans requête supplémentaire côté
 * frontend.
 */
class ListMyCompaniesAction
{
    /** @return Collection<int, Company> */
    public function execute(User $user): Collection
    {
        return Company::query()
            ->whereHas('claims', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', CompanyClaimStatus::Approved))
            ->with(['country', 'subscriptions' => fn ($query) => $query->latest('id')->limit(1)])
            ->get();
    }
}
