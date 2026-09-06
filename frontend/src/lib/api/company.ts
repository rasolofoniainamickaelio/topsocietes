import { cache } from "react";
import { apiFetch, ApiError } from "@/lib/api/client";
import type { Company } from "@/types/company";

/**
 * `null` sur 404 — laisse l'appelant décider (typiquement `notFound()`).
 * Mémoïsée par requête (React `cache`, même patron que
 * `getCurrentCountry`) : `generateMetadata` et le composant de page
 * appellent tous deux `getCompany` pour la même requête sans dupliquer
 * l'appel réseau.
 */
export const getCompany = cache(async function getCompany(
  country: string,
  slug: string,
): Promise<Company | null> {
  try {
    const { data } = await apiFetch<{ data: Company }>(
      country,
      `/companies/${slug}`,
    );

    return data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }

    throw error;
  }
});
