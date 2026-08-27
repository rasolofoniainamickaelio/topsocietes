# ADR 0004 — Authentification Sanctum SPA et intégration Stripe sans Cashier

- **Statut** : Acceptée
- **Date** : 2026-08-27

## Contexte

Dernier morceau de l'API publique : Geo, Taxonomy, Company, Moderation, Ads et Seo sont déjà exposés en lecture seule. Billing est le seul domaine qui suppose qu'un visiteur s'authentifie, pour revendiquer une fiche entreprise (`company_claims`) puis y souscrire un abonnement payant. Il fallait donc trancher un mécanisme d'authentification API, et la façon d'intégrer Stripe.

## Décision 1 — Authentification par Laravel Sanctum, mode SPA

Le frontend Next.js et l'API Laravel sont deux applications distinctes mais servies sous des sous-domaines d'un même domaine parent (`{pays}.topsocietes.com` côté frontend, l'API centralisée côté backend). Sanctum en **mode SPA** (cookies de session, pas de token à stocker côté client) convient exactement à ce cas : pas de token à gérer en JS (surface XSS réduite), CSRF géré nativement par la session Laravel.

Mis en place :
- `EnsureFrontendRequestsAreStateful` en tête du groupe de middleware `api` (`bootstrap/app.php`).
- `SESSION_DOMAIN=.topsocietes.test` (point en tête pour partager le cookie entre sous-domaines), `SANCTUM_STATEFUL_DOMAINS` en `.env`.
- `config/cors.php` : `supports_credentials: true`, origines limitées à `FRONTEND_URL` et un pattern régulier pour les sous-domaines pays — jamais un sous-domaine par pays codé en dur (CLAUDE.md §6.6).
- **Pas** de migration `personal_access_tokens`, **pas** de trait `HasApiTokens` sur `User` : le mode SPA pur n'en a pas besoin, ajouter ces éléments aurait ouvert une seconde surface d'authentification (token bearer) non demandée.
- Un compte créé via `POST /v1/auth/register` reçoit toujours le rôle `company_owner` (jamais un rôle donnant accès au back-office Filament, voir `User::canAccessPanel()` et ADR 0002).

## Décision 2 — Stripe via le SDK direct, pas le trait `Billable` de Cashier

ADR 0001 mentionnait Cashier pour « encapsuler la gestion des abonnements ». Mais le schéma réellement construit en Phase 2 possède sa **propre** table `subscriptions` (`company_id`, `provider`, `provider_subscription_id`, `status` via `SubscriptionStatus`...), distincte de celle que la migration native de Cashier créerait, et `users` n'a pas de colonne `stripe_id`. Brancher le trait `Billable` sur ce schéma déjà posé et testé aurait exigé de le réécrire ou de dupliquer l'état de facturation à deux endroits.

Décision : `laravel/cashier` reste une dépendance déclarée (rien n'est retiré sans le dire, CLAUDE.md §9), mais ni son trait `Billable` ni ses migrations ne sont utilisés. L'intégration passe directement par le SDK `stripe/stripe-php` (déjà tiré transitivement par Cashier), derrière une interface d'isolation — même patron que `SearchEngineInterface` (ADR 0001) :

- `App\Domain\Billing\Contracts\CheckoutGatewayInterface` — un seul point de couplage avec Stripe, remplaçable par un faux en test (aucun appel réseau dans la suite de tests) et par un futur `PaypalCheckoutGateway` (`PaymentProvider::Paypal` existe déjà dans le catalogue d'enums).
- `App\Domain\Billing\Services\StripeCheckoutGateway` — implémentation, liée dans `AppServiceProvider`.
- `App\Domain\Billing\Actions\HandleStripeWebhookAction` — prend un type d'événement et un payload déjà parsés (aucune dépendance au SDK), pour rester testable sans HTTP. Seul `StripeWebhookController` touche le SDK brut, pour la vérification de signature (`Stripe\Webhook::constructEvent`) ; la route `/webhooks/stripe` est exemptée de la vérification CSRF (elle n'est jamais appelée par le frontend).

## Conséquences

- Sans clés Stripe réelles (`STRIPE_KEY`/`STRIPE_SECRET`/`STRIPE_WEBHOOK_SECRET` vides en `.env`), le code est complet et la suite de tests passe intégralement (gateway faussée, `HandleStripeWebhookAction` testée avec des payloads construits à la main) — mais aucune vérification bout-en-bout réelle contre l'API Stripe n'est possible tant que des clés de test ne sont pas fournies.
- Si Cashier doit un jour être réellement adopté (ex. migration du schéma `subscriptions` vers celui de Cashier), cette ADR devra être révisée en conséquence.
