# ADR 0001 — Stack technique et principes de déploiement

- **Statut** : Acceptée
- **Date** : 2026-08-18

## Contexte

TOPsocietes.com est un annuaire d'entreprises multi-pays (`fr` `be` `tn` `ma` `dz` `qc`) construit comme une usine à contenus : import de fichiers d'entreprises volumineux (plusieurs millions de lignes), enrichissement géographique, génération de contenu par IA en batch, et publication de millions de pages SEO. Le modèle économique repose sur le démasquage payant du bloc Contact.

Trois contraintes structurantes pèsent sur tous les choix ci-dessous :

1. Aucun appel IA ne doit être déclenché par la lecture d'une page publique — tout est pré-généré.
2. Les contenus locaux sont mutualisés entre entreprises d'un même territoire.
3. Les coordonnées masquées ne doivent jamais transiter vers un client tant qu'aucun abonnement n'est actif.

## Décisions

### Backend — Laravel 12 (PHP 8.3+) + Filament v3

Laravel offre l'écosystème le plus mature pour ce profil de projet : queues (Horizon), Eloquent avec support PostGIS via des colonnes `GEOGRAPHY`, Cashier pour l'intégration Stripe, et un ORM suffisamment discipliné pour supporter la séparation stricte Domain/Action imposée par `CLAUDE.md`. Filament v3 fournit un back-office admin généré à partir des ressources Eloquent, ce qui évite de développer une interface de pilotage IA et de gestion des contestations from scratch.

### Frontend — Next.js 15 (App Router, TypeScript strict)

Le volume de pages (des millions, combinatoires) impose du rendu statique/ISR avec revalidation ciblée (`revalidateTag`) plutôt que du SSR à la volée. L'App Router et les Server Components par défaut limitent le JavaScript expédié au client, ce qui compte pour le SEO et les Core Web Vitals sur un site à fort trafic organique.

### Base de données — PostgreSQL 17 + PostGIS, pg_trgm, unaccent

PostGIS est requis pour les calculs de proximité (rayon de 20 km, rattachement quartier/commune par `ST_Contains`) qui sont au cœur du produit (bloc de proximité, résolution géo en Phase 4). `pg_trgm` et `unaccent` couvrent la recherche floue et insensible aux accents nécessaire à la recherche multi-critères (Phase 9) sans dépendance à un moteur de recherche externe.

### Recherche — PostgreSQL FTS derrière `SearchEngineInterface`

Le FTS natif de PostgreSQL suffit au volume initial et évite d'opérer un service supplémentaire (contrainte explicite : « ne pas introduire de service supplémentaire sans validation »). L'abstraction `SearchEngineInterface` isole le Domain de ce choix technique : si le volume ou les besoins de pertinence l'exigent plus tard (Meilisearch, Typesense), seule l'implémentation change, jamais le code appelant.

### Cache & files — Redis + Laravel Horizon

Le pipeline d'import, la génération IA en batch et le pré-calcul de proximité sont tous des traitements longs qui doivent sortir du cycle requête/réponse. Horizon donne une visibilité opérationnelle (débit, échecs, retries) indispensable sur des volumes de plusieurs millions d'enregistrements.

### Cartes — Leaflet + react-leaflet + tuiles OSM

Solution open-source sans coût de licence par volume de cartes affichées, adaptée à un site où la carte de proximité apparaît sur chaque fiche entreprise.

### Paiement — Stripe via Laravel Cashier

Cashier encapsule la gestion des abonnements, webhooks et cycles de facturation, ce qui réduit le code custom nécessaire pour le démasquage/remasquage automatique des contacts (Phase 12).

### Déploiement — VPS Ubuntu natif (Nginx, PHP-FPM, PM2, Supervisor), Docker interdit

Choix imposé par `CLAUDE.md`. Rationale retenue : contrôle total des ressources sur un hébergement à fort volume de trafic et de traitement batch, absence de couche d'abstraction supplémentaire à opérer (réseau, volumes, orchestration), coût d'infrastructure prévisible. La contrepartie est une reproductibilité d'environnement moindre qu'avec des conteneurs — compensée par une procédure d'installation native strictement documentée (`docs/deployment.md`, Phase 14) et des releases symlinkées pour permettre un rollback immédiat.

## Conséquences

- Toute nouvelle dépendance de service (moteur de recherche externe, message broker, etc.) doit repasser par une validation explicite avant introduction.
- Le pipeline IA et le pipeline d'import doivent être conçus comme des Jobs Horizon dès leur première ligne de code — aucun raccourci synchrone n'est acceptable, même en développement.
- L'absence de Docker impose que `docs/deployment.md` (Phase 14) documente une installation native reproductible à la main, versionnée comme n'importe quel autre artefact du projet.
