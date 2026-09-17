import Link from "next/link";
import { SectionCard } from "@/components/ui/SectionCard";
import type { InternalLink } from "@/types/city-page";

/**
 * Liens descendants (Phase 13) : sous-divisions pour une région, communes
 * pour un département — seulement celles déjà synchronisées.
 */
export function ChildDivisionsList({ divisions }: { divisions: InternalLink[] }) {
  const routable = divisions.filter((child): child is InternalLink & { path: string } => child.path !== null);

  if (routable.length === 0) {
    return null;
  }

  return (
    <SectionCard theme="district" title="Territoires rattachés">
      <ul className="flex flex-wrap gap-2">
        {routable.map((child) => (
          <li key={child.path}>
            <Link
              href={child.path}
              className="rounded-[var(--radius-chip)] px-3 py-1 text-sm"
              style={{
                backgroundColor: "color-mix(in srgb, var(--t-district) 12%, var(--paper))",
                color: "var(--t-district)",
              }}
            >
              {child.label}
            </Link>
          </li>
        ))}
      </ul>
    </SectionCard>
  );
}
