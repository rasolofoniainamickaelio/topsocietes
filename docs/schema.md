# Modèle de données — TOPsocietes.com

Ce document précède toute migration (Phase 0). Il couvre l'ensemble du modèle nécessaire aux phases 1 à 13 pour éviter des migrations contradictoires plus tard. Chaque groupe de tables correspond à une phase du plan ; le détail exact des colonnes (types, index, contraintes) sera affiné au moment de la migration de la phase concernée, mais les relations et clés étrangères ci-dessous sont considérées stables.

## Notes de conception ouvertes

- **`companies` / unité légale vs établissement** (décision client en attente n°2) : le schéma ne préjuge pas de la réponse. `companies` porte une colonne `parent_company_id` (nullable, auto-référence) qui permet de modéliser un établissement rattaché à une unité légale sans réécriture de table si la décision tranche pour le multi-niveau. Si la décision retient "un seul niveau", cette colonne reste simplement inutilisée.
- **`countries.code`** (décision client en attente n°1, sous-domaine Algérie) : `code` est un simple `varchar` contraint par une table de configuration, jamais codé en dur dans l'application — aucun impact sur le schéma.
- Toutes les tables volumineuses (`companies`, `facts`, `content_blocks`, `combinatorial_pages`) sont conçues pour un accès par curseur (`cursor()`/`lazy()`), jamais `all()`/`get()`.
- `facts` et `local_contents` utilisent une relation polymorphique (`subject_type` + `subject_id`) car un contenu local peut être rattaché à une ville, un quartier, une division administrative ou un secteur — jamais à une entreprise (règle métier gravée n°2).

## Diagramme

```mermaid
erDiagram
    COUNTRIES ||--o{ ADMIN_DIVISIONS : "contient"
    COUNTRIES ||--o{ ACTIVITY_CODES : "spécialise (optionnel)"
    COUNTRIES ||--o{ COMPANIES : "partitionne"
    COUNTRIES ||--o{ PLANS : "tarifie"
    COUNTRIES ||--o{ REDIRECTIONS : "scope"
    COUNTRIES ||--o{ COMBINATORIAL_PAGES : "scope"

    ADMIN_DIVISIONS ||--o{ ADMIN_DIVISIONS : "parent / enfant"
    ADMIN_DIVISIONS ||--o{ CITIES : "contient"
    CITIES ||--o{ DISTRICTS : "contient"

    ACTIVITY_SECTORS ||--o{ ACTIVITY_SECTORS : "parent / enfant"
    ACTIVITY_SECTORS ||--o{ ACTIVITY_CODES : "regroupe"

    ACTIVITY_CODES ||--o{ COMPANIES : "classe"
    CITIES ||--o{ COMPANIES : "localise"
    DISTRICTS ||--o{ COMPANIES : "localise (optionnel)"
    COMPANIES ||--o| COMPANIES : "établissement -> unité légale (optionnel)"
    COMPANIES ||--|| COMPANY_CONTACTS : "masque"
    COMPANIES ||--o{ SUBSCRIPTIONS : "démasque via"
    COMPANIES ||--o{ CONTESTATIONS : "conteste"

    SOURCES ||--o{ FACTS : "justifie"
    FACTS }o--|| ADMIN_DIVISIONS : "sujet (polymorphique)"
    FACTS }o--|| CITIES : "sujet (polymorphique)"
    FACTS }o--|| DISTRICTS : "sujet (polymorphique)"
    FACTS }o--|| ACTIVITY_SECTORS : "sujet (polymorphique)"

    LOCAL_CONTENTS ||--o{ CONTENT_BLOCKS : "compose"
    LOCAL_CONTENTS }o--|| CITIES : "sujet (polymorphique)"
    LOCAL_CONTENTS }o--|| DISTRICTS : "sujet (polymorphique)"
    LOCAL_CONTENTS }o--|| ADMIN_DIVISIONS : "sujet (polymorphique)"
    LOCAL_CONTENTS }o--|| ACTIVITY_SECTORS : "sujet (polymorphique)"

    AI_GENERATION_JOBS }o--|| LOCAL_CONTENTS : "génère"
    AI_GENERATION_JOBS ||--o{ SOURCES : "s'appuie sur (facts)"

    ACTIVITY_CODES ||--o{ COMBINATORIAL_PAGES : "croise"
    CITIES ||--o{ COMBINATORIAL_PAGES : "croise"
    ACTIVITY_SECTORS ||--o{ COMBINATORIAL_PAGES : "croise"

    PLANS ||--o{ SUBSCRIPTIONS : "souscrit"
    SUBSCRIPTIONS ||--o{ WEBHOOK_EVENTS : "synchronise"

    COUNTRIES {
        bigint id PK
        varchar code "fr be tn ma dz qc"
        varchar name
        varchar locale
        varchar currency
        varchar subdomain
        varchar timezone
        jsonb config
        boolean is_active
    }

    ADMIN_DIVISIONS {
        bigint id PK
        bigint country_id FK
        bigint parent_id FK "nullable, auto-référence"
        varchar level "region department province ..."
        varchar name
        varchar code
        varchar slug
    }

    CITIES {
        bigint id PK
        bigint admin_division_id FK
        bigint country_id FK
        varchar name
        varchar slug
        varchar postal_code
        integer population
        geography centroid "Point,4326"
    }

    DISTRICTS {
        bigint id PK
        bigint city_id FK
        varchar name
        varchar slug
        geography boundary "Polygon,4326 nullable"
        geography centroid "Point,4326"
    }

    ACTIVITY_SECTORS {
        bigint id PK
        bigint parent_id FK "nullable, auto-référence"
        varchar code
        varchar name
        varchar slug
    }

    ACTIVITY_CODES {
        bigint id PK
        bigint sector_id FK
        bigint country_id FK "nullable, code spécifique pays"
        varchar code "NAF NACE ..."
        varchar label
    }

    COMPANIES {
        bigint id PK "partition key: country_id"
        bigint country_id FK
        bigint parent_company_id FK "nullable, établissement -> unité légale"
        varchar legal_id "SIREN, matricule fiscal, ..."
        varchar name
        varchar legal_form
        bigint activity_code_id FK
        bigint city_id FK
        bigint district_id FK "nullable"
        varchar address
        geography location "Point,4326"
        varchar status "enum: draft published archived"
        varchar slug
        timestamp published_at
        timestamp created_at
    }

    COMPANY_CONTACTS {
        bigint id PK
        bigint company_id FK "unique"
        varchar phone "nullable"
        varchar email "nullable"
        varchar website "nullable"
        boolean is_masked "défaut true"
        timestamp updated_at
    }

    SOURCES {
        bigint id PK
        varchar type "insee osm wikipedia manual ..."
        varchar url "nullable"
        timestamp retrieved_at
        smallint reliability_score
    }

    FACTS {
        bigint id PK
        varchar subject_type "polymorphique"
        bigint subject_id "polymorphique"
        varchar key
        jsonb value
        bigint source_id FK
        timestamp verified_at
    }

    LOCAL_CONTENTS {
        bigint id PK
        varchar subject_type "polymorphique"
        bigint subject_id "polymorphique"
        varchar type "history nature leisure specialty stats district"
        varchar locale
        varchar status "enum: draft published"
    }

    CONTENT_BLOCKS {
        bigint id PK
        bigint local_content_id FK "nullable"
        bigint company_id FK "nullable, blocs propres à une fiche (contact, à propos)"
        varchar type "t-company t-contact t-nearby ..."
        jsonb data
        integer position
        varchar generated_by "ai manual"
        timestamp validated_at "nullable"
    }

    AI_GENERATION_JOBS {
        bigint id PK
        varchar subject_type "polymorphique"
        bigint subject_id "polymorphique"
        varchar prompt_version
        varchar status "enum: queued generating validating rejected published"
        jsonb input_facts
        jsonb output
        jsonb validation_result
        timestamp created_at
    }

    REDIRECTIONS {
        bigint id PK
        bigint country_id FK
        varchar from_path
        varchar to_path
        smallint http_status "défaut 301"
        varchar reason
        timestamp created_at
    }

    COMBINATORIAL_PAGES {
        bigint id PK
        bigint country_id FK
        bigint activity_code_id FK "nullable"
        bigint activity_sector_id FK "nullable"
        bigint city_id FK "nullable"
        bigint admin_division_id FK "nullable"
        smallint publication_score
        varchar status "enum: draft published unpublished"
        varchar slug
    }

    PLANS {
        bigint id PK
        bigint country_id FK "nullable, tarification régionale"
        varchar name
        integer price_cents
        varchar interval "month year"
        varchar stripe_price_id
    }

    SUBSCRIPTIONS {
        bigint id PK
        bigint company_id FK
        bigint plan_id FK
        varchar stripe_subscription_id
        varchar status "enum Cashier"
        timestamp current_period_end
    }

    WEBHOOK_EVENTS {
        bigint id PK
        varchar provider "stripe"
        varchar event_id "unique"
        jsonb payload
        timestamp processed_at "nullable"
    }

    CONTESTATIONS {
        bigint id PK
        bigint company_id FK
        varchar submitted_by
        text reason
        varchar status "enum: open reviewing resolved rejected"
        text resolution_note "nullable"
        timestamp resolved_at "nullable"
    }

    ADMIN_USERS {
        bigint id PK
        varchar name
        varchar email "unique"
        varchar password
        varchar role
    }
```

## Correspondance phases → tables

| Phase | Tables |
|---|---|
| 1 — Socle référentiel | `countries`, `admin_divisions`, `cities`, `districts`, `activity_sectors`, `activity_codes` |
| 2 — Entreprises & PostGIS | `companies`, `company_contacts` |
| 3 — Pipeline d'import | pas de nouvelle table de domaine ; journalisation dans `Support` (hors Domain) |
| 4 — Résolution géo | pas de nouvelle table ; peuple `companies.district_id` et pré-calcule la proximité |
| 5 — Contenus mutualisés | `sources`, `facts`, `local_contents`, `content_blocks` |
| 6 — Chaîne IA | `ai_generation_jobs` |
| 10 — SEO | `redirections` |
| 11 — Pages combinatoires | `combinatorial_pages` |
| 12 — Monétisation | `plans`, `subscriptions`, `webhook_events` |
| 13 — Back-office | `admin_users`, + ressources Filament sur les tables existantes ; `contestations` |
