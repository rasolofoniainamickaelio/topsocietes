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
      entity_type: string;
      entity_id: number;
      is_indexable: boolean;
    };
