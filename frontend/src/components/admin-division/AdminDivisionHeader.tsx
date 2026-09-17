import { Breadcrumb, type BreadcrumbItem } from "@/components/ui/Breadcrumb";
import type { AdminDivisionPage } from "@/types/admin-division-page";

/**
 * Fil d'Ariane (Phase 13) : le pays est toujours cliquable ; la division
 * parente aussi, mais seulement si sa propre page est déjà synchronisée —
 * `links.parent` retombe déjà sur le pays sinon (`TerritoryLinkingService`),
 * jamais un lien cassé (CLAUDE.md §6.5). Le libellé du niveau ("Région",
 * "Préfecture"...) vient de `countries.admin_level_labels`, jamais un mot
 * codé en dur (CLAUDE.md §6.6).
 */
export function AdminDivisionHeader({ division, countryName }: { division: AdminDivisionPage; countryName: string }) {
  const items: BreadcrumbItem[] = [{ label: countryName, href: "/" }];

  if (division.links?.parent && division.links.parent.path && division.links.parent.path !== "/") {
    items.push({ label: division.links.parent.label, href: division.links.parent.path });
  }
  items.push({ label: division.name });

  return (
    <header className="border-b border-[var(--border)] pb-6">
      <Breadcrumb items={items} />
      {division.level_label && (
        <p className="text-sm font-medium text-[var(--ink-muted)]">{division.level_label}</p>
      )}
      <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
        {division.name}
      </h1>
    </header>
  );
}
