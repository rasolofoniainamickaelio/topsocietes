import Link from "next/link";

export interface BreadcrumbItem {
  label: string;
  /** Absent : affiché en texte simple (pas encore de page à cibler, ou page courante) — jamais un `<Link>` vers `undefined` (CLAUDE.md §6.5). */
  href?: string;
}

/**
 * Composant partagé (Phase 13) : conçu pour la hiérarchie territoriale
 * complète (pays → région → département → ville → quartier), réutilisable
 * pour les 5 prochains types de page sans modification.
 */
export function Breadcrumb({ items }: { items: BreadcrumbItem[] }) {
  return (
    <nav aria-label="Fil d'Ariane" className="mb-4 flex flex-wrap items-center gap-1 text-sm text-[var(--ink-muted)]">
      {items.map((item, index) => (
        <span key={`${item.label}-${index}`} className="flex items-center gap-1">
          {index > 0 && <span aria-hidden="true">›</span>}
          {item.href ? (
            <Link href={item.href} className="hover:underline">
              {item.label}
            </Link>
          ) : (
            <span>{item.label}</span>
          )}
        </span>
      ))}
    </nav>
  );
}
