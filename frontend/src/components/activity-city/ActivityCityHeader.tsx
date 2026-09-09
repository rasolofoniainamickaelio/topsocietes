import type { ActivityCityPage } from "@/types/activity-city";

/**
 * En-tête de la page activité×ville (Phase 12) — pas de gabarit préexistant
 * à suivre (CLAUDE.md §5 ne couvre que la fiche entreprise), même échelle
 * typographique que `CompanyIdentityBlock` pour rester cohérent.
 */
export function ActivityCityHeader({ page }: { page: ActivityCityPage }) {
  return (
    <header className="border-b border-[var(--border)] pb-6">
      <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
        {page.activity.label} à {page.city.name}
      </h1>
    </header>
  );
}
