import type { Company } from "@/types/company";

/**
 * En-tête de la fiche (CLAUDE.md §5, maquette "En-tête entreprise") — pas
 * un bloc thématique parmi d'autres, toujours présent puisque `legal_name`
 * est un champ obligatoire côté BDD.
 */
export function CompanyIdentityBlock({ company }: { company: Company }) {
  return (
    <header className="border-b border-[var(--border)] pb-6">
      <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
        {company.legal_name}
      </h1>
      {company.trade_name && (
        <p className="mt-1 text-[var(--ink-muted)]">{company.trade_name}</p>
      )}
      <span className="mt-3 inline-block rounded-[var(--radius-chip)] bg-[var(--surface)] px-2 py-0.5 text-sm text-[var(--ink-muted)] capitalize">
        {company.status}
      </span>
    </header>
  );
}
