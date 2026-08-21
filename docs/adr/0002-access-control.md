# ADR 0002 — Rôles et permissions

- **Statut** : Acceptée
- **Date** : 2026-08-21

## Contexte

Le schéma DB (Phase 2) crée déjà les tables `spatie/laravel-permission` et un `RoleSeeder` posant les 5 rôles (`super_admin`, `admin`, `moderator`, `content_manager`, `company_owner`) sans permission assignée — explicitement hors périmètre de cette phase. Avant toute autre phase (restructuration `Domain/`, résolution géo, import), il faut fixer le modèle d'autorisation : quel rôle peut faire quoi, sur quel domaine, et comment cela s'articule avec le masquage des contacts (CLAUDE.md §6.3) et la revendication de fiche (`company_claims`).

Aucune Resource Filament ni contrôleur API n'existe encore. Cette phase pose donc le catalogue de permissions et les `Policy` là où une logique dépasse "le rôle a la permission" — pas l'intégration UI, qui viendra domaine par domaine.

## Décisions

### Catalogue de permissions (`App\Enums\PermissionName`)

Convention `domaine.action` (`view` = lecture, `manage` = création/modification/suppression) :

| Permission | Domaine couvert |
|---|---|
| `companies.view`, `companies.manage` | `companies`, `establishments` |
| `contacts.manage`, `contacts.view_masked` | `company_contacts` (`view_masked` = voir les coordonnées démasquées hors flux d'abonnement, ex. support/modération) |
| `claims.view`, `claims.review` | `company_claims` |
| `content.manage` | `content_sections`, `city_contents`, `district_contents`, `activity_contents`, `city_activity_contents` |
| `sources.manage` | `sources`, `source_documents`, `facts`, `content_source_links` |
| `ai_pipeline.manage` | `ai_prompts`, `ai_generation_jobs`, `ai_generation_logs` |
| `imports.manage` | `import_mappings`, `import_batches`, `import_errors` |
| `geo.manage` | `countries`, `admin_divisions`, `cities`, `districts`, `city_neighbors`, `points_of_interest` |
| `taxonomy.manage` | `activity_nomenclatures`, `activities`, `sectors`, `activity_mappings` |
| `seo.manage` | `routes` (`PageRoute`), `redirects`, `sitemap_shards`, `page_publication_rules`, `page_publication_decisions` |
| `disputes.view`, `disputes.review` | `dispute_reports`, `dispute_events` |
| `ads.manage` | `ad_slots`, `ad_campaigns`, `service_links` |
| `billing.view`, `billing.manage` | `plans`, `subscriptions`, `payments`, `contact_visibility_events` |
| `users.manage` | `users`, gestion des rôles |

### Matrice rôle → permissions (`RoleSeeder`)

- **super_admin** : aucune permission assignée en base — bypass total via `Gate::before` (`AppServiceProvider::boot()`). Choix délibéré plutôt que d'assigner explicitement toutes les permissions : aucune désynchronisation possible le jour où une nouvelle permission est ajoutée sans mettre à jour le seeder.
- **admin** : toutes les permissions sauf `users.manage` — couvre l'opérationnel complet (entreprises, contenus, sources, pipeline IA, imports, géo, taxonomie, SEO, litiges, pub, billing) mais jamais la gestion des comptes/rôles des autres utilisateurs internes.
- **moderator** : `companies.view`, `contacts.view_masked`, `claims.view`, `claims.review`, `disputes.view`, `disputes.review` — investigation et modération, aucune modification de fiche ni accès contenu/billing.
- **content_manager** : `content.manage`, `sources.manage`, `ai_pipeline.manage` — strictement le domaine éditorial.
- **company_owner** : **aucune permission globale**. Ce rôle ne porte aucune permission spatie ; il sert uniquement de marqueur "cet utilisateur est un représentant d'entreprise". Son accès à sa propre fiche vient exclusivement du lien `CompanyClaim` (`status = approved`, `user_id` = lui), vérifié directement dans `CompanyPolicy::update()`. À ne pas lire comme un oubli au moment d'un futur audit du seeder.

### Contacts — même règle pour le propriétaire que pour le public

Un `company_owner`, même propriétaire vérifié de sa fiche, ne voit ses coordonnées démasquées que dans les mêmes conditions qu'un visiteur tiers (abonnement actif sur sa fiche). `CompanyContactPolicy::viewMasked()` ne contient donc aucun cas particulier pour le propriétaire — lecture stricte de CLAUDE.md §6.3, décision explicitement actée plutôt que supposée.

### Portée des classes `Policy`

Seuls les modèles où l'autorisation dépasse "le rôle a la permission X" reçoivent une classe dédiée aujourd'hui : `Company` (scope propriétaire), `CompanyContact` (masquage), `CompanyClaim` (revendication + vue de sa propre demande), `DisputeReport`, `User`.

Pour tous les autres domaines du catalogue (géo, taxonomie, contenus, sources, pipeline IA, imports, SEO, pub, billing), aucune `Policy` n'existe encore — ils n'ont ni Resource Filament ni contrôleur pour les consommer. **Convention à suivre quand leur Resource sera créée** : vérifier directement `auth()->user()->can(PermissionName::X->value)` dans `canViewAny()`/`canCreate()`/etc. de la Resource Filament (Laravel/Filament n'exigent pas de classe `Policy` pour un contrôle par simple permission). Si une logique propriétaire ou conditionnelle apparaît pour l'un de ces modèles, promouvoir en `Policy` à ce moment-là plutôt que d'anticiper une classe vide.

## Conséquences

- Toute nouvelle Resource Filament doit déclarer son autorisation dès sa création — via une `Policy` existante si le modèle en a une, sinon via une vérification `can()` directe suivant la convention ci-dessus.
- Toute nouvelle permission doit être ajoutée à `PermissionName` puis répercutée dans la matrice de `RoleSeeder` — les deux évoluent ensemble, jamais l'un sans l'autre.
- `company_owner` restant sans permission globale, toute future fonctionnalité "espace propriétaire" (front Next.js / API) doit systématiquement passer par une vérification de revendication approuvée, jamais par un simple `hasRole('company_owner')`.
