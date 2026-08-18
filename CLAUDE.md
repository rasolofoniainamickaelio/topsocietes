# CLAUDE.md — TOPsocietes.com

Conventions permanentes du projet. À lire avant toute action. Ces règles priment sur les comportements par défaut.

---

## 1. Stack

| Couche | Choix | Non négociable |
|---|---|---|
| Backend | Laravel 12, PHP 8.3+ | oui |
| Back-office | Filament v3 | oui |
| Frontend | Next.js 15 App Router, TypeScript strict | oui |
| Styles | Tailwind CSS + tokens CSS custom | oui |
| Base | PostgreSQL 17 + PostGIS, pg_trgm, unaccent | oui |
| Cache & files | Redis + Laravel Horizon | oui |
| Cartes | Leaflet + react-leaflet + tuiles OSM | oui |
| Paiement | Stripe via Laravel Cashier | oui |
| Recherche | PostgreSQL FTS derrière `SearchEngineInterface` | interface obligatoire |
| Déploiement | VPS Ubuntu natif : Nginx, PHP-FPM, PM2, Supervisor | oui |

**Docker est interdit.** Aucun `Dockerfile`, `docker-compose.yml`, ni référence à un conteneur, y compris dans la documentation.

Gestionnaires : `composer` côté backend, `pnpm` côté frontend.

---

## 2. Arborescence

```
topsocietes/
├── CLAUDE.md
├── docs/
│   ├── adr/                    # une décision d'architecture = un fichier numéroté
│   ├── schema.md               # modèle de données (Mermaid)
│   ├── deployment.md           # installation native, sans Docker
│   └── prompts/                # prompts IA versionnés, jamais hors du repo
├── backend/
│   ├── app/
│   │   ├── Domain/             # cœur métier, un dossier par contexte
│   │   │   ├── Company/
│   │   │   │   ├── Actions/    # une classe = une opération, méthode execute()
│   │   │   │   ├── Data/       # DTO (spatie/laravel-data)
│   │   │   │   ├── Enums/
│   │   │   │   ├── Events/
│   │   │   │   ├── Jobs/
│   │   │   │   ├── Models/
│   │   │   │   ├── Queries/    # requêtes de lecture complexes
│   │   │   │   └── Services/
│   │   │   ├── Geo/
│   │   │   ├── Content/        # contenus mutualisés, facts, sources
│   │   │   ├── Ai/             # génération, validation anti-hallucination
│   │   │   ├── Import/
│   │   │   ├── Seo/            # slugs, redirections, sitemaps, scoring
│   │   │   ├── Billing/
│   │   │   └── Moderation/     # contestations
│   │   ├── Http/Api/V1/
│   │   │   ├── Controllers/    # orchestration uniquement
│   │   │   ├── Requests/
│   │   │   └── Resources/
│   │   ├── Filament/
│   │   └── Support/            # helpers transverses, sans dépendance métier
│   ├── database/migrations/
│   └── tests/{Unit,Feature}/
└── frontend/
    └── src/
        ├── app/
        │   ├── (site)/         # pages publiques
        │   └── api/            # revalidation, health
        ├── components/
        │   ├── blocks/         # un composant par bloc de contenu
        │   ├── ui/             # primitives sans logique métier
        │   └── layout/
        ├── lib/
        │   ├── api/            # client typé vers l'API Laravel
        │   ├── seo/            # metadata, JSON-LD, breadcrumbs
        │   └── country/        # résolution du sous-domaine
        ├── styles/tokens.css
        └── types/
```

**Règle de dépendance :** `Http` → `Domain` → `Support`. Jamais l'inverse. Un `Domain` n'importe rien de `Http` ni de `Filament`.

---

## 3. Conventions backend

- **Un contrôleur orchestre, il ne décide pas.** Toute logique vit dans une `Action` à responsabilité unique exposant `execute()`.
- **Un modèle Eloquent = données + relations + casts.** Pas de logique métier, pas d'appel externe, pas de scope contenant des règles de gestion complexes.
- Typage strict partout : `declare(strict_types=1);`, types de retour explicites, aucun `mixed` non justifié.
- **Aucune donnée nue entre couches** : DTO en entrée d'Action, `JsonResource` en sortie d'API.
- Enums PHP natifs pour tous les statuts. Jamais de chaîne magique.
- Injection par constructeur. Pas de façades dans le `Domain` (sauf `DB` pour les transactions).
- Tout traitement long est un `Job` en queue, jamais dans le cycle requête/réponse.
- Requêtes volumineuses : `cursor()` / `lazy()`, jamais `all()` ni `get()` sur une table millionnaire.
- Chaque migration crée ses index dans la même migration que la table.
- Formatage : `vendor/bin/pint`. Analyse statique : `larastan` niveau 6 minimum.

**Nommage :** `CreateCompanyAction`, `CompanyData`, `CompanyStatus`, `ImportCompaniesJob`, `NearbyPointsQuery`.

---

## 4. Conventions frontend

- **Server Components par défaut.** `"use client"` uniquement pour l'interactivité réelle (carte, filtres, formulaires) et justifié en commentaire.
- Rendu : ISR + `revalidateTag`, déclenché par webhook Laravel après publication. Pas de SSR à la volée sur les pages lourdes.
- **Registre de blocs** : l'API renvoie `blocks: [{ type, data }]`, le frontend mappe `type` vers un composant. Aucune logique conditionnelle codée en dur page par page. Un `type` inconnu est ignoré silencieusement, jamais une erreur de rendu.
- Un composant = un fichier = une responsabilité. Props typées explicitement, jamais `any`.
- Data fetching dans `lib/api/`, jamais de `fetch` inline dans un composant.
- Accessibilité au plancher : focus visible, contraste AA minimum, `prefers-reduced-motion` respecté, landmarks corrects.
- Nommage fichiers : `kebab-case.tsx`. Nommage composants : `PascalCase`.

---

## 5. Système de design

### Intention

Le sujet est un registre : des faits administratifs entourés de contexte territorial. Le design doit rendre visible, en permanence, **la frontière entre un fait sourcé et une prose rédigée**. C'est aussi ce que le cahier des charges exige sur le fond (anti-hallucination) — la mise en forme le rend lisible.

**Élément signature : le fil des faits.** Tout atome factuel — identifiant d'entreprise, code activité, date de création, effectif, distance en mètres, population — est composé en **monospace**, sur une pastille teintée. La prose générée reste en typographie courante. Le lecteur distingue d'un coup d'œil ce qui vient d'un registre de ce qui a été rédigé. Sur le bloc de proximité, les distances alignées à gauche forment une règle graduée verticale : le fil des faits devient un repère spatial.

C'est le seul endroit où le design prend un risque. Tout le reste est calme et discipliné.

### Couleurs

Base neutre, plus une couleur par thématique. Chaque thématique dispose d'une teinte de fond (`tint`), d'une couleur de trait (`stroke`) et d'une couleur de texte (`ink`), pour rester lisible sans transformer la page en arc-en-ciel.

```css
:root {
  /* Neutres */
  --ink:           #101418;
  --ink-muted:     #5A646F;
  --paper:         #FFFFFF;
  --surface:       #F4F6F8;
  --border:        #E1E5EA;

  /* Marque */
  --brand:         #143A5A;  /* bleu ardoise — identité, en-têtes */
  --brand-ink:     #0C2437;
  --signal:        #F2B705;  /* jaune signalétique — accent, un seul par écran */

  /* Thématiques de blocs */
  --t-company:     #143A5A;
  --t-contact:     #0F766E;
  --t-nearby:      #6D28D9;
  --t-district:    #B45309;
  --t-history:     #8C5A2B;
  --t-nature:      #2F7A3E;
  --t-leisure:     #C2410C;
  --t-specialty:   #A21C4B;
  --t-stats:       #334155;
  --t-sector:      #1E5AA8;

  /* Rayons et espacement */
  --radius-card:   20px;
  --radius-chip:   8px;
  --space-block:   24px;
}
```

Application d'un bloc thématique : fond `color-mix(in srgb, var(--t-x) 6%, var(--paper))`, bordure 1px `color-mix(in srgb, var(--t-x) 24%, transparent)`, filet vertical gauche 3px pleine saturation, icône et titre en `--t-x`. **Pas d'ombre portée** : la hiérarchie vient de la teinte et du filet, pas de l'élévation.

### Typographie

| Rôle | Police | Usage |
|---|---|---|
| Display | **Archivo** (600/700, largeur expanded sur H1) | titres de blocs, très visibles comme demandé |
| Texte | **Public Sans** (400/500/600) | prose, listes, FAQ |
| Données | **IBM Plex Mono** (500) | identifiants, codes, dates, distances, chiffres |

Échelle : `H1 32/1.15` · `H2 24/1.25` · `H3 19/1.3` · `body 16/1.6` · `small 14/1.5` · `mono 14/1.4`. Mobile : H1 descend à 27, le reste inchangé.

Titres en **casse phrase**, jamais en capitales. Aucune graisse inférieure à 400 sur du texte courant.

### Layout

```
MOBILE (référence)                DESKTOP ≥ 1024px
┌──────────────────────┐          ┌────────────────────┬──────────┐
│ En-tête entreprise   │          │ En-tête entreprise │  PUB 1   │
├──────────────────────┤          ├────────────────────┤          │
│ ▍À propos            │          │ ▍À propos          ├──────────┤
├──────────────────────┤          ├────────────────────┤  PUB 2   │
│ ▍Contact (masqué)    │          │ ▍Contact           │          │
├──────────────────────┤          ├────────────────────┤ (sticky) │
│ ▍PUB 1               │          │ ▍Carte proximité   │          │
├──────────────────────┤          ├────────────────────┴──────────┤
│ ▍Carte proximité     │          │ ▍Blocs suivants               │
│   287 m ─ Lieu       │          └───────────────────────────────┘
│   1,2 km ─ Lieu      │
├──────────────────────┤          Colonne principale : max 720px.
│ ▍Quartier            │          Rail droit : 300px, sticky.
├──────────────────────┤
│ ▍PUB 2               │
├──────────────────────┤
│ ▍Histoire · FAQ ...  │
└──────────────────────┘
```

Sur mobile, les deux publicités s'insèrent après le bloc Contact et après le bloc Quartier — visibles sans couper une lecture en cours. Leurs dimensions sont réservées en CSS pour éviter tout décalage de mise en page.

### Motion

Micro-interactions uniquement : ouverture d'un accordéon FAQ, filtres de carte, survol de marqueur. Durées 120–200 ms, courbe `ease-out`. **Aucune animation d'apparition au scroll.** `prefers-reduced-motion: reduce` désactive tout.

### Écriture d'interface

Voix active, casse phrase, verbes simples. Un libellé d'action décrit ce qui se passe : « Afficher les coordonnées », pas « Souscrire ». Un état vide oriente : « Aucun lieu répertorié dans un rayon de 20 km. » Une erreur explique et propose une issue, sans s'excuser et sans vague.

---

## 6. Règles métier gravées

1. **Aucun appel IA en lecture de page.** Si tu écris du code qui appelle un modèle depuis un contrôleur de lecture, tu t'es trompé.
2. **Contenus locaux mutualisés** : rattachés à une ville, un quartier, une division ou un secteur — jamais à une entreprise.
3. **Contacts masqués côté serveur** : jamais présents dans la réponse API tant que l'abonnement n'est pas actif.
4. **Slug et identifiant interne immuables.** Tout changement de slug crée automatiquement une redirection 301.
5. **Bloc sans données suffisantes = bloc absent.** Aucun texte de remplissage.
6. **Comportement par pays piloté par la table `countries`.** Aucun branchement conditionnel sur un code pays dans le code.
7. **L'IA reçoit des faits structurés, jamais un sujet libre.** Un validateur post-génération rejette toute mention de réputation, solvabilité, chiffres financiers, litiges, certifications, avis ou effectifs non adossée à une source.

---

## 7. Tests et qualité

Avant d'annoncer une tâche terminée :

```bash
# backend
cd backend && vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test

# frontend
cd frontend && pnpm lint && pnpm typecheck && pnpm test
```

Couverture attendue en priorité : masquage/démasquage des contacts, scoring de publication, slugification et collisions, validateur anti-hallucination, pipeline d'import (idempotence et reprise), tunnel d'abonnement de bout en bout.

---

## 8. Git

Conventional Commits : `feat(company): ...`, `fix(import): ...`, `perf(geo): ...`, `docs(adr): ...`.
Une branche par phase : `phase/03-import-pipeline`. Jamais de commit direct sur `main`.
`.env` n'est jamais commité ni modifié sans me le dire.

---

## 9. Ce que tu ne fais pas sans me demander

- Ajouter une dépendance non listée ici.
- Modifier le schéma d'une table déjà peuplée.
- Lancer une commande d'import ou de génération IA sur un volume réel.
- Introduire un service supplémentaire (moteur de recherche externe, broker, etc.).
- Toucher à la configuration serveur ou au pipeline CI/CD.
- Supprimer du code existant, y compris des commentaires utiles.
