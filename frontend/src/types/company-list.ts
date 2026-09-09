import type { Company } from "@/types/company";

/**
 * Miroir de la pagination par curseur standard Laravel
 * (`CompanyIndexController`, `ListCompaniesAction::cursorPaginate`) —
 * jamais de `total`/`current_page` : le curseur ne connaît pas la
 * profondeur totale (CLAUDE.md §6, Phase 14).
 */
export interface CompanyListPage {
  data: Company[];
  meta: {
    next_cursor: string | null;
    prev_cursor: string | null;
  };
}
