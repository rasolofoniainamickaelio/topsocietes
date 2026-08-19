<?php

declare(strict_types=1);

namespace App\Enums;

/** Réutilisé sur `subscriptions.provider` et `payments.provider`. */
enum PaymentProvider: string
{
    case Stripe = 'stripe';
    case Paypal = 'paypal';
}
