import Link from "next/link";
import { SectionCard } from "@/components/ui/SectionCard";
import type { InternalLink } from "@/types/city-page";

/**
 * Liens descendants vers les pages activité×ville de cette commune (Phase
 * 12, déjà servies) — seul lien descendant réellement construit pour
 * cette première page territoriale (Phase 13).
 */
export function CityActivitiesList({ activities }: { activities: InternalLink[] }) {
  const routable = activities.filter((activity): activity is InternalLink & { path: string } => activity.path !== null);

  if (routable.length === 0) {
    return null;
  }

  return (
    <SectionCard theme="sector" title="Activités présentes">
      <ul className="flex flex-wrap gap-2">
        {routable.map((activity) => (
          <li key={activity.path}>
            <Link
              href={activity.path}
              className="rounded-[var(--radius-chip)] px-3 py-1 text-sm"
              style={{
                backgroundColor: "color-mix(in srgb, var(--t-sector) 12%, var(--paper))",
                color: "var(--t-sector)",
              }}
            >
              {activity.label}
            </Link>
          </li>
        ))}
      </ul>
    </SectionCard>
  );
}
