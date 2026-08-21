<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\ContactVisibilityTrigger;
use App\Domain\Billing\Models\ContactVisibilityEvent;
use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactVisibilityEvent>
 */
class ContactVisibilityEventFactory extends Factory
{
    protected $model = ContactVisibilityEvent::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'contact_id' => null,
            'action' => ContactVisibilityAction::Unmasked,
            'triggered_by' => ContactVisibilityTrigger::SubscriptionActivated,
            'subscription_id' => null,
            'user_id' => null,
        ];
    }
}
