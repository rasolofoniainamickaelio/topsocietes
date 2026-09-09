import Link from "next/link";
import { SectionCard } from "@/components/ui/SectionCard";
import { listCompanies } from "@/lib/api/companies";
import { CompanyListItem } from "@/components/activity-city/CompanyListItem";

/**
 * Server Component asynchrone (pas d'interactivité réelle — pagination par
 * lien simple `?cursor=`, cohérent avec "Server Components par défaut",
 * CLAUDE.md §4). Partagé entre la page activité×ville (Phase 12, filtre
 * ville+activité) et la page ville (Phase 13, filtre ville seule) —
 * `activitySlug`/`activityLabel` absents pour cette dernière.
 */
export async function CompanyListSection({
  country,
  pagePath,
  citySlug,
  activitySlug,
  cursor,
  cityName,
  activityLabel,
}: {
  country: string;
  pagePath: string;
  citySlug: string;
  activitySlug?: string;
  cursor?: string;
  cityName: string;
  activityLabel?: string;
}) {
  const page = await listCompanies(country, { city: citySlug, activity: activitySlug, cursor });

  return (
    <SectionCard theme="sector" title="Entreprises">
      {page.data.length === 0 ? (
        <p>
          {activityLabel
            ? `Aucune entreprise répertoriée pour ${activityLabel.toLowerCase()} à ${cityName}.`
            : `Aucune entreprise répertoriée à ${cityName}.`}
        </p>
      ) : (
        <ul>
          {page.data.map((company) => (
            <CompanyListItem key={company.slug} company={company} />
          ))}
        </ul>
      )}
      {page.meta.next_cursor && (
        <Link
          href={`${pagePath}?cursor=${page.meta.next_cursor}`}
          className="mt-3 inline-block text-sm text-[var(--brand)] hover:underline"
        >
          Voir plus d&apos;entreprises
        </Link>
      )}
    </SectionCard>
  );
}
