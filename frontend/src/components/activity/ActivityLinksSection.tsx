import Link from "next/link";
import { SectionCard } from "@/components/ui/SectionCard";
import type { InternalLink } from "@/types/city-page";

/**
 * Liens descendants/transversaux de la page activité seule (Phase 13) :
 * secteurs qui la regroupent, villes où elle est déjà pratiquée — chacun
 * seulement si sa page est déjà synchronisée.
 */
export function ActivityLinksSection({ title, theme, links }: { title: string; theme: "sector" | "district"; links: InternalLink[] }) {
  const routable = links.filter((link): link is InternalLink & { path: string } => link.path !== null);

  if (routable.length === 0) {
    return null;
  }

  return (
    <SectionCard theme={theme} title={title}>
      <ul className="flex flex-wrap gap-2">
        {routable.map((link) => (
          <li key={link.path}>
            <Link
              href={link.path}
              className="rounded-[var(--radius-chip)] px-3 py-1 text-sm"
              style={{
                backgroundColor: `color-mix(in srgb, var(--t-${theme}) 12%, var(--paper))`,
                color: `var(--t-${theme})`,
              }}
            >
              {link.label}
            </Link>
          </li>
        ))}
      </ul>
    </SectionCard>
  );
}
