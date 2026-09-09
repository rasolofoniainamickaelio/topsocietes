import { Breadcrumb, type BreadcrumbItem } from "@/components/ui/Breadcrumb";
import type { CityPage } from "@/types/city-page";

/**
 * Fil d'Ariane (Phase 13) : seul le pays (page d'accueil) est cliquable —
 * région et département n'ont pas encore de page à cibler, affichés en
 * texte simple plutôt qu'un lien cassé (CLAUDE.md §6.5).
 */
export function CityHeader({ city, countryName }: { city: CityPage; countryName: string }) {
  const items: BreadcrumbItem[] = [{ label: countryName, href: "/" }];

  if (city.admin_division?.region) {
    items.push({ label: city.admin_division.region });
  }
  if (city.admin_division?.name) {
    items.push({ label: city.admin_division.name });
  }
  items.push({ label: city.name });

  return (
    <header className="border-b border-[var(--border)] pb-6">
      <Breadcrumb items={items} />
      <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
        {city.name}
      </h1>
    </header>
  );
}
