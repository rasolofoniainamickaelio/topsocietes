# ADR 0003 — Documentation OpenAPI/Swagger de l'API

- **Statut** : Acceptée
- **Date** : 2026-08-26

## Contexte

L'API (`app/Http/Api/V1/`) ne compte pour l'instant qu'un seul endpoint (`CountryController`), mais va grossir domaine par domaine (Company, Geo, Content...). Une documentation interactive OpenAPI/Swagger est nécessaire pour que le frontend Next.js et d'éventuels clients externes puissent explorer les contrats sans lire le code Laravel.

## Décisions

### `dedoc/scramble` plutôt que `darkaonline/l5-swagger`

Scramble génère le schéma OpenAPI automatiquement depuis les routes, `FormRequest` et `JsonResource` existants — aucune annotation à écrire ni à maintenir à la main. `l5-swagger` (wrapper de `zircote/swagger-php`) exige des annotations PHP sur chaque contrôleur/Resource : plus explicite mais plus verbeux, et le risque de désynchronisation entre l'annotation et le code réel grandit avec le nombre d'endpoints. Vu que l'API va être construite domaine par domaine à partir de zéro, la génération automatique évite cette dette dès le départ.

### Accès à l'UI interactive restreint hors production

`config/scramble.php` embarque `Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess`, qui autorise nativement l'environnement `local` et sinon vérifie un Gate `viewApiDocs`. Ce Gate est défini dans `AppServiceProvider::boot()` :

```php
Gate::define('viewApiDocs', fn (): bool => ! app()->environment('production'));
```

`docs/api` (UI) et `docs/api.json` (schéma) partagent le même groupe de routes chez Scramble — les séparer aurait demandé de sortir de la configuration statique (`Scramble::registerApi()`) pour un gain marginal (un JSON OpenAPI seul n'expose aucune donnée, juste la forme des contrats). Les deux sont donc gatés ensemble : ouverts sur tout environnement non-production, fermés (403) en production.

## Conséquences

- **Windows local uniquement** : `laravel/horizon` déclare `ext-pcntl`/`ext-posix` comme dépendances, deux extensions POSIX absentes de PHP sous Windows natif. `composer require`/`update` échoue sans `--ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`. Sans incidence sur le déploiement Ubuntu (ADR 0001) où ces extensions existent — mais à savoir pour toute future commande composer sur une machine de dev Windows.
- **Base de données de dev locale** (`topsocietes`, distincte de `topsocietes_test` utilisée par la suite de tests) créée et migrée à cette occasion — Scramble introspecte le schéma réel des modèles via une connexion vivante pour construire les schémas de réponse (`ModelInfo`), donc `docs/api.json` échoue si la base de dev n'existe pas.
- Toute nouvelle route API bénéficie de la doc automatiquement dès qu'elle est enregistrée sous `api/*` (préfixe par défaut de `withRouting(api: ...)`) — aucune étape supplémentaire à retenir.
