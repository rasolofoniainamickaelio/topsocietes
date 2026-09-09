import { apiFetch } from "@/lib/api/client";
import type { CompanyListPage } from "@/types/company-list";

/**
 * Liste d'entreprises filtrée (`CompanyIndexController`, déjà fonctionnel,
 * pagination par curseur) — jamais mémoïsée via `cache()` : le `cursor`
 * change à chaque page, `getCompany`/`getActivityCity` le sont parce
 * qu'ils sont rappelés à l'identique dans `generateMetadata` et le
 * composant de page pour la même requête.
 */
export async function listCompanies(
  country: string,
  { city, activity, cursor }: { city?: string; activity?: string; cursor?: string },
): Promise<CompanyListPage> {
  const params = new URLSearchParams();
  if (city) params.set("city", city);
  if (activity) params.set("activity", activity);
  if (cursor) params.set("cursor", cursor);

  return apiFetch<CompanyListPage>(country, `/companies?${params.toString()}`);
}
