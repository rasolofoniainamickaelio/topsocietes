import { Breadcrumb, type BreadcrumbItem } from "@/components/ui/Breadcrumb";
import type { ActivityCityPage } from "@/types/activity-city";

/**
 * En-tête + fil d'Ariane (Phase 12/13) : pays → ville → activité → page
 * composite courante. Les chemins montants viennent de l'API (`city.path`,
 * `activity.path`) — jamais reconstruits ici (CLAUDE.md §6.6).
 */
export function ActivityCityHeader({
  page,
  countryName,
}: {
  page: ActivityCityPage;
  countryName: string;
}) {
  const items: BreadcrumbItem[] = [{ label: countryName, href: "/" }];

  if (page.city.path) {
    items.push({ label: page.city.name, href: page.city.path });
  } else {
    items.push({ label: page.city.name });
  }

  if (page.activity.path) {
    items.push({ label: page.activity.label, href: page.activity.path });
  } else {
    items.push({ label: page.activity.label });
  }

  items.push({ label: `${page.activity.label} à ${page.city.name}` });

  return (
    <header className="border-b border-[var(--border)] pb-6">
      <Breadcrumb items={items} />
      <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
        {page.activity.label} à {page.city.name}
      </h1>
    </header>
  );
}
