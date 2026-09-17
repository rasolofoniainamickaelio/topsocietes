import { Breadcrumb, type BreadcrumbItem } from "@/components/ui/Breadcrumb";
import type { CityPage } from "@/types/city-page";

/**
 * Fil d'Ariane (Phase 13) : pays → région → département → ville. Région et
 * département ne sont cliquables que si `links.region` / `links.department`
 * portent un `path` (route déjà synchronisée) — sinon texte simple
 * (CLAUDE.md §6.5).
 */
export function CityHeader({ city, countryName }: { city: CityPage; countryName: string }) {
  const items: BreadcrumbItem[] = [{ label: countryName, href: "/" }];

  const regionLink = city.links?.region;
  const departmentLink = city.links?.department;

  if (regionLink?.path) {
    items.push({ label: regionLink.label, href: regionLink.path });
  } else if (city.admin_division?.region) {
    items.push({ label: city.admin_division.region });
  }

  if (departmentLink?.path) {
    items.push({ label: departmentLink.label, href: departmentLink.path });
  } else if (city.admin_division?.name) {
    items.push({ label: city.admin_division.name });
  }

  items.push({ label: city.name });

  return (
    <header className="border-b border-[var(--border)] pb-6">
      <Breadcrumb items={items} />
      <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
        {city.name}
      </h1>
      {city.companies_count != null && city.companies_count > 0 && (
        <p className="mt-2 text-sm text-[var(--ink-muted)]">
          {city.companies_count.toLocaleString("fr-FR")} entreprise
          {city.companies_count > 1 ? "s" : ""}
        </p>
      )}
    </header>
  );
}
