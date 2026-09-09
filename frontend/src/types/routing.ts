/**
 * Miroir de `App\Domain\Seo\Actions\ResolvePathAction` (backend, Phase 16) :
 * résout un chemin exact soit vers une redirection (301 permanent, ou 410
 * gone — seules valeurs acceptées par la contrainte CHECK sur
 * `redirects.status_code`), soit vers la route qui le sert.
 */
export type ResolvedPath =
  | { type: "redirect"; to: string; status_code: 301 | 410 }
  | {
      type: "route";
      page_type: string;
      /**
       * `null` pour une page composite (ex. activité×ville, Phase 12) :
       * pas d'entité Eloquent unique, `routes.entity_type`/`entity_id`
       * restent `null` en base pour ce cas.
       */
      entity_type: string | null;
      entity_id: number | null;
      is_indexable: boolean;
    };
