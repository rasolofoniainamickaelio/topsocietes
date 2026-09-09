import Link from "next/link";
import type { Company } from "@/types/company";

/**
 * Premier composant de type "carte d'entreprise dans une liste" du projet
 * (Phase 12) — aucun gabarit existant à réutiliser. `company.path` absent
 * (fiche pas encore synchronisée, Phase 16) → entrée omise silencieusement
 * plutôt qu'un lien cassé (CLAUDE.md §6.5).
 */
export function CompanyListItem({ company }: { company: Company }) {
  if (!company.path) {
    return null;
  }

  return (
    <li className="border-b border-[var(--border)] py-3 last:border-b-0">
      <Link href={company.path} className="font-medium text-[var(--brand)] hover:underline">
        {company.trade_name ?? company.legal_name}
      </Link>
    </li>
  );
}
