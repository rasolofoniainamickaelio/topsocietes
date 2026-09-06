# Phase 20 — Optimisation des performances

Audit ciblé sur les deux points d'entrée les plus chargés de l'API publique : la fiche entreprise (`GET /v1/{country}/companies/{slug}`) et la recherche (`GET /v1/{country}/search`). Fait après les phases 1 à 18 : optimiser avant que les fonctionnalités existent n'a pas de sens.

## 1. N+1 sur les secteurs de l'activité (fiche entreprise)

**Avant** : `CompanyPageBlocksQuery` assemblait les blocs territoriaux d'une fiche en appelant `ActivityContentBlocksQuery::forSector()` **une fois par secteur** de l'activité de l'entreprise. Une activité rattachée à 5 secteurs déclenchait donc 5 requêtes `activity_contents` distinctes, en plus de la requête pour l'activité elle-même — un nombre de requêtes proportionnel au nombre de secteurs, jamais borné.

**Après** : `ActivityContentBlocksQuery::forSectors()` regroupe tous les `sector_id` concernés dans une seule requête (`whereIn`), puis répartit les résultats par secteur en mémoire. Le nombre de requêtes ne dépend plus du nombre de secteurs.

**Mesure** — `tests/Feature/Api/CompanyShowPerformanceTest.php` : compare le nombre de requêtes exécutées par `CompanyPageBlocksQuery::execute()` pour une entreprise dont l'activité a 1 secteur contre une entreprise dont l'activité en a 8. Résultat : nombre de requêtes identique dans les deux cas (test de non-régression, pas une mesure ponctuelle — il échouera si le N+1 est réintroduit).

## 2. Mise en cache des blocs de contenu de la fiche entreprise

`CompanyPageBlocksQuery` composait à chaque lecture la chaîne de repli géographique (quartier → commune → région), les contenus d'activité/secteur et le contenu croisé ville×activité — plusieurs requêtes à chaque affichage d'une fiche, pour un contenu qui ne change qu'à la publication d'un bloc territorial (bien plus rare qu'une lecture).

**Décision** : mise en cache (`Cache::remember`, TTL 15 min) plutôt qu'une invalidation fine par observer sur chacune des 5 tables de contenu concernées (`city_contents`, `district_contents`, `admin_division_contents`, `activity_contents`, `city_activity_contents`) — ce graphe d'observers aurait été disproportionné par rapport au gain. `CompanyObserver` invalide la clé quand la ville, le quartier ou l'activité de l'entreprise elle-même change (ce qui change les tables de contenu applicables) ; une republication de contenu territorial met donc jusqu'à 15 minutes à apparaître sur les fiches concernées — une fenêtre jugée acceptable, à revoir si l'usage réel l'exige.

Même patron déjà en place pour `InternalLinkingService` (Phase 15, M7).

## 3. Index manquants

Deux requêtes fréquentes des phases 16/17 n'étaient couvertes par aucun index (migration `2026_09_06_090000_add_performance_indexes_for_seo_tables.php`) :

- `redirects (country_id, to_path)` — utilisé par `CreateRedirectAction` pour repointer toute redirection existante qui visait un chemin renommé (jamais de chaîne A→B→C, Phase 16). Seul `from_path` était indexé (via la contrainte unique).
- `routes (country_id, page_type, is_indexable)` — utilisé par `GenerateSitemapsAction` pour sélectionner les routes indexables d'un pays, par type de page (Phase 17). Seul `page_type` seul était indexé.

Les tables ajoutées en cours de route (`content_revisions`, `ai_budgets`) avaient déjà leurs index dans leur migration d'origine (`content_revisions (content_type, content_id)`, `ai_budgets (country_id, period)` en UNIQUE) — rien à ajouter.

## 4. Recherche (`CompanySearchController`)

Déjà couvert par `tests/Feature/Search/SearchPerformanceTest.php` (Phase 14, M6) : recherche multi-critères mesurée sous 200 ms sur un volume de 3 000 entreprises. Aucune régression constatée après les phases 15 à 20 (le test reste vert).

## Méthode de suivi

Les deux tests de performance (`CompanyShowPerformanceTest`, `SearchPerformanceTest`) sont marqués `->group('performance')` et servent de garde-fou de non-régression — ils échouent si un futur changement réintroduit un N+1 ou dégrade le temps de réponse en dessous du seuil mesuré, plutôt que de documenter un chiffre qui se périme dès le prochain changement de volume de données.
