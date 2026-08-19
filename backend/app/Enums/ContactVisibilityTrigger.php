<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactVisibilityTrigger: string
{
    case SubscriptionActivated = 'subscription_activated';
    case SubscriptionExpired = 'subscription_expired';
    case Admin = 'admin';
    case Import = 'import';
}
