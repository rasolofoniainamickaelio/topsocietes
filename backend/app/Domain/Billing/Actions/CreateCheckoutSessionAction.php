<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Contracts\CheckoutGatewayInterface;
use App\Domain\Billing\Data\CreateCheckoutSessionData;
use App\Domain\Billing\Models\Plan;
use App\Domain\Company\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * L'autorisation (revendication approuvée sur `$company`) est vérifiée par
 * l'appelant via `CompanyPolicy::update` — cette Action suppose déjà
 * l'utilisateur légitime à agir pour cette entreprise.
 */
class CreateCheckoutSessionAction
{
    public function __construct(private readonly CheckoutGatewayInterface $gateway) {}

    public function execute(User $user, Company $company, CreateCheckoutSessionData $data): string
    {
        $plan = Plan::query()
            ->where('code', $data->plan_code)
            ->where('is_active', true)
            ->first();

        if ($plan === null) {
            throw new ModelNotFoundException;
        }

        return $this->gateway->createSubscriptionCheckoutSession(
            $user,
            $company,
            $plan,
            $data->success_url,
            $data->cancel_url,
        );
    }
}
