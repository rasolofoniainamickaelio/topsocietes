<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Domain\Billing\Actions\HandleStripeWebhookAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Seul endroit qui touche le SDK Stripe brut (vérification de signature) —
 * délègue tout le reste à `HandleStripeWebhookAction`, testable sans HTTP.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, HandleStripeWebhookAction $action): Response
    {
        $secret = (string) config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret,
            );
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response()->noContent(400);
        }

        $action->execute($event->type, $event->data->object->toArray());

        return response()->noContent();
    }
}
