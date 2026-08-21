<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactVisibilityAction;
use App\Enums\ContactVisibilityTrigger;
use App\Models\Company;
use App\Models\ContactVisibilityEvent;
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
