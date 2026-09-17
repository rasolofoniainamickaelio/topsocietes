import { Breadcrumb, type BreadcrumbItem } from "@/components/ui/Breadcrumb";
import type { ActivityPage } from "@/types/activity-page";

/**
 * Fil d'Ariane (Phase 13) : le pays est toujours cliquable ; l'activité
 * parente aussi, mais seulement si sa propre page est déjà synchronisée
 * (`links.parent`, `TerritoryLinkingService::forActivity`) — sinon absente,
 * jamais un lien cassé (CLAUDE.md §6.5).
 */
export function ActivityHeader({ activity, countryName }: { activity: ActivityPage; countryName: string }) {
  const items: BreadcrumbItem[] = [{ label: countryName, href: "/" }];

  if (activity.links?.parent?.path) {
    items.push({ label: activity.links.parent.label, href: activity.links.parent.path });
  }
  items.push({ label: activity.label });

  return (
    <header className="border-b border-[var(--border)] pb-6">
      <Breadcrumb items={items} />
      <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
        {activity.label}
      </h1>
    </header>
  );
}
