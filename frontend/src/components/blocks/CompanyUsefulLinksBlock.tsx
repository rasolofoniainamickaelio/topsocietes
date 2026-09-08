import Link from "next/link";
import { SectionCard } from "@/components/ui/SectionCard";
import type { Company, InternalLink } from "@/types/company";

type RoutableLink = { link: InternalLink; href: string };

/**
 * `path` (Phase 16, backend) n'est rempli que pour les types de page ayant
 * une route servie par le frontend — les autres restent silencieusement
 * masqués plutôt que d'afficher un lien cassé (CLAUDE.md §6.5). Jamais de
 * reconstruction d'URL ici : le backend a déjà résolu le chemin exact.
 */
function routableLinks(links: InternalLink[]): RoutableLink[] {
  return links
    .filter((link): link is InternalLink & { path: string } => link.path !== null)
    .map((link) => ({ link, href: link.path }));
}

/**
 * Maillage interne (Phase 15, backend) : le groupe est omis silencieusement
 * dès qu'aucun de ses liens n'est encore routable côté front, plutôt que
 * d'afficher un lien cassé (CLAUDE.md §6.5).
 */
function LinkGroup({ title, entries }: { title: string; entries: RoutableLink[] }) {
  if (entries.length === 0) {
    return null;
  }

  return (
    <div>
      <h3 className="mb-2 text-sm font-semibold text-[var(--ink-muted)]">{title}</h3>
      <ul className="flex flex-col gap-1">
        {entries.map(({ link, href }) => (
          <li key={href}>
            <Link
              href={href}
              className="text-[var(--t-nearby)] underline-offset-2 hover:underline"
            >
              {link.label}
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}

export function CompanyUsefulLinksBlock({ company }: { company: Company }) {
  const links = company.links;

  if (!links) {
    return null;
  }

  const sameTradeInCity = routableLinks(links.sameTradeInCity);
  const nearby = routableLinks(links.nearby);

  if (sameTradeInCity.length === 0 && nearby.length === 0) {
    return null;
  }

  return (
    <SectionCard theme="nearby" title="Liens utiles">
      <div className="flex flex-col gap-4">
        <LinkGroup title="Même activité dans la commune" entries={sameTradeInCity} />
        <LinkGroup title="Entreprises à proximité" entries={nearby} />
      </div>
    </SectionCard>
  );
}
