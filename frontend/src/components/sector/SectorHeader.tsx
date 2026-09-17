import { Breadcrumb, type BreadcrumbItem } from "@/components/ui/Breadcrumb";
import type { SectorPage } from "@/types/sector-page";

/**
 * Fil d'Ariane (Phase 13) : le pays est toujours cliquable. Un secteur
 * n'a pas de "parent" — pas de niveau intermédiaire à afficher.
 */
export function SectorHeader({ sector, countryName }: { sector: SectorPage; countryName: string }) {
  const items: BreadcrumbItem[] = [{ label: countryName, href: "/" }, { label: sector.name }];

  return (
    <header className="border-b border-[var(--border)] pb-6">
      <Breadcrumb items={items} />
      <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
        {sector.name}
      </h1>
    </header>
  );
}
