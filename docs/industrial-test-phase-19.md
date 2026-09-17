# Phase 19 — Test industriel (10k–50k entreprises)

Objectif : mesurer le pipeline d’import **sans** lancer des millions de fiches, ni la génération IA de masse. Livrable = rapport chiffré (`import:report`) pour préparer la Phase 20.

Infra VPS / Nginx / Horizon multi-serveurs : **hors scope** (phases déploiement).

## Prérequis

1. Seed référentiel Maroc + mapping :
   ```bash
   cd backend
   php artisan db:seed --class=CountrySeeder
   php artisan db:seed --class=MoroccoAdminDivisionSeeder
   php artisan db:seed --class=MoroccoCitySeeder
   php artisan db:seed --class=MoroccoActivitySeeder
   php artisan db:seed --class=MoroccoImportMappingSeeder
   php artisan db:seed --class=PagePublicationRuleSeeder
   ```
2. Redis + worker queue `imports` (Horizon `supervisor-imports` ou `queue:work --queue=imports`).
3. Fichier source : exporter `docs/MAROC_ENTREPRISE.xlsx` (local, gitignored) en **CSV UTF-8**.

## Procédure

```bash
# 1. Échantillon 10 000 lignes → storage/app/private/...
php artisan import:prepare-sample ../docs/MAROC_ENTREPRISE.csv --limit=10000

# 2. Dry-run (aucune entreprise écrite)
php artisan import:companies imports/samples/sample-….csv ma --limit=10000 --dry-run
# Attendre la fin du worker, puis :
php artisan import:report {batchId}

# 3. Import réel (ex. 10k puis 50k)
php artisan import:companies imports/samples/sample-….csv ma --limit=10000
php artisan import:report {batchId}

# 4. Post-import utile (sans IA)
php artisan seo:sync-company-routes --country=ma
php artisan geo:recalculate-companies-counts
```

Reprise / erreurs : `import:resume`, `import:retry-errors`.

## Critères de succès

- Aucun plantage worker / OOM sur 50k lignes
- Taux d’erreur documenté (`error_rate_pct`)
- `%` résolution ville / activité documentés
- `rows_per_minute` renseigné (started_at / finished_at)
- Rapport JSON archivé (`import:report {id} --json`) comme baseline Phase 20

## Hors scope volontaire

- Génération IA batch, sitemaps de prod, Lighthouse mobile, auth Nginx préprod
