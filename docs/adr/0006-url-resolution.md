# ADR 0006 — Résolution d'URL côté frontend : catch-all générique + `/resolve`, jamais de pattern codé en dur

- **Statut** : Acceptée
- **Date** : 2026-09-08

## Contexte

Le backend calcule depuis longtemps la structure d'URL définitive d'une fiche entreprise (`BuildCompanyPathAction`, pattern `/{city}/{slug}-{public_id}`, configurable par pays via `countries.url_patterns` — identique sur les 6 pays aujourd'hui, mais la colonne reste `jsonb` et l'Action accepte une variation future) et sait résoudre n'importe quel chemin vers une entité ou une redirection 301/410 (`ResolvePathAction`, exposée en `GET /resolve`). Rien de tout ça n'était branché côté Next.js : la fiche entreprise restait servie sur une URL provisoire (`/companies/{slug}`), documentée comme telle dans le code depuis la Phase 06.

Deux contraintes ont pesé sur la conception :
- CLAUDE.md §6.6 : « Comportement par pays piloté par la table `countries`. Aucun branchement conditionnel sur un code pays dans le code. » — le frontend ne doit donc jamais reconstruire un chemin à partir du pattern (nombre de segments, présence de `{city}`, etc.), même si ce pattern est identique partout aujourd'hui.
- La colonne `companies.slug` n'a **aucune contrainte unique** en base (seul `(country_id, national_id)` l'est). Le `{public_id}` (ULID, unique) dans le pattern d'URL n'est donc pas décoratif : c'est lui qui identifie une fiche sans ambiguïté en cas d'homonymes, jamais le slug seul.

## Décision

Un unique catch-all Next.js, `app/(site)/[...path]/page.tsx`, reçoit tout chemin qui ne correspond à aucune route plus spécifique (`/` reste servi par `(site)/page.tsx`). Il reconstruit le chemin exact demandé et appelle `GET /resolve` :
- une redirection 301 → `permanentRedirect()` (App Router, 308 — équivalent SEO d'un 301) ;
- une redirection 410, ou un chemin totalement inconnu → `notFound()` (pas d'équivalent 410 natif dans l'App Router pour un Server Component sans passer par un Route Handler dédié — écart mineur assumé, jamais généré aujourd'hui côté backend) ;
- une route dont `page_type !== "company"` → `notFound()` (les 5 autres types de page n'ont pas encore de page frontend — chantier distinct) ;
- une route `company` → nouveau lookup par `id` interne (`GET /companies/lookup/{id}` → `CompanyShowByIdController`, miroir de `CompanyShowController`), jamais par slug, précisément à cause de l'absence de contrainte unique ci-dessus.

`CompanyResource` expose désormais `public_id` et `path` (le garde-fou qui les cachait devient obsolète puisque cette structure d'URL est enfin servie). Le maillage interne (`InternalLinkingService`) expose de même un `path` déjà construit sur chaque lien vers une fiche entreprise, plutôt que de laisser le frontend le reconstruire.

L'ancienne route `/companies/[slug]/page.tsx` est conservée (jamais supprimée, CLAUDE.md §9) mais devient un simple redirecteur permanent vers `company.path` — aucun lien déjà partagé ne casse.

## Conséquences

- Le frontend reste totalement agnostique du pattern d'URL réel : si `countries.url_patterns` diverge un jour entre pays (ou change de forme), aucun code frontend n'a à changer, seul le backend construit et résout les chemins.
- Une entreprise renommée (changement de slug) continue de fonctionner sans rien de plus : `CompanyObserver` crée déjà la redirection, `/resolve` la sert automatiquement.
- Le sitemap (`GenerateSitemapsAction`), qui générait déjà des URL sur ce format, pointe désormais vers de vraies pages au lieu de 404 — corrigé sans changement de son côté.
- Chaque page de fiche entreprise coûte un aller-retour `/resolve` de plus qu'avant (avant d'atteindre `/companies/lookup/{id}`) — accepté pour l'instant ; à revisiter si la Phase 20 (performance) le désigne comme un point chaud mesuré.
- Les 5 autres types de page restent à construire : ce même catch-all les servira plus tard en étendant simplement le `switch` sur `page_type`, sans nouveau mécanisme de résolution à inventer.
