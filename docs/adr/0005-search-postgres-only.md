# ADR 0005 — Recherche 100% PostgreSQL, Meilisearch écarté

- **Statut** : Acceptée
- **Date** : 2026-09-06

## Contexte

Le cahier des charges initial (carte Trello "Phase 14 — Moteur de recherche") demande une architecture hybride : PostgreSQL full-text comme source de vérité, plus Meilisearch comme index dérivé pour l'autocomplétion et la tolérance aux fautes de frappe, avec synchronisation par queue et repli automatique sur Postgres si Meilisearch tombe.

Le CLAUDE.md (§1, tableau des choix techniques) fige pourtant la recherche sur « PostgreSQL FTS derrière `SearchEngineInterface` — interface obligatoire », sans mention de Meilisearch. Le §9 va plus loin : « Introduire un service supplémentaire (moteur de recherche externe, broker, etc.) » figure explicitement dans la liste des actions à ne jamais prendre sans demander. Les deux documents étaient donc en contradiction directe sur ce point précis.

## Décision

Le CLAUDE.md prime (il l'annonce lui-même : « Ces règles priment sur les comportements par défaut »). Confirmé explicitement avec le mandant avant d'implémenter quoi que ce soit : **aucun Meilisearch n'est introduit**. `App\Domain\Search\Contracts\SearchEngineInterface`, déjà posée, reste la seule frontière du domaine Recherche, avec `App\Domain\Search\Services\PostgresSearchEngine` comme unique implémentation.

Ce que `PostgresSearchEngine` fournit déjà et qui couvre l'essentiel du besoin fonctionnel de la carte Trello, sans second service :
- `tsvector`/`plainto_tsquery('french_unaccent', …)` pour la recherche structurée, combiné à un repli `pg_trgm` (opérateur `%`) pour la tolérance aux fautes de frappe sur `legal_name` — l'équivalent Postgres-only de ce qu'apporterait un index Meilisearch dédié.
- Un terme purement numérique est traité comme un préfixe SIREN (`LIKE 'terme%'`, indexé), jamais comme une recherche floue.
- Pagination **par curseur** partout où le volume peut être grand (`searchCompanies`, `autocomplete`), jamais par offset — voir la règle "pas de LIKE %…% ni d'OFFSET sur des millions de lignes" de la carte Trello elle-même. `ListCompaniesAction` (listing public, historiquement en `LengthAwarePaginator`) a été alignée sur ce même principe dans cette passe.
- Aucun mécanisme de "repli si le moteur externe tombe" n'est nécessaire : il n'y a qu'un seul moteur, donc rien à faire tomber.

## Conséquences

- Pas de nouveau service à déployer, superviser ou maintenir sur le VPS (Docker reste interdit, CLAUDE.md §1 — un Meilisearch aurait de toute façon posé la question de son mode d'installation).
- Pas de synchronisation par queue à écrire ni de risque de désynchronisation entre deux sources de vérité.
- Autocomplétion et tolérance aux fautes restent portées par `pg_trgm`, mesurablement moins riches qu'un moteur dédié sur des very-long-tail queries — acceptable au vu du volume actuel (des dizaines de milliers d'entreprises par pays, pas des dizaines de millions).
- Si un volume réel futur démontre que `pg_trgm` ne suffit plus (temps de réponse mesurés au-delà de l'objectif, voir tests de performance de `tests/Feature/Search/`), cette ADR devra être révisée avant d'introduire quoi que ce soit — jamais en réintroduisant Meilisearch au détour d'une autre tâche sans repasser par cette même discussion.
