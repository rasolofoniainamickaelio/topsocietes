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

/**
 * Lookup par `id` interne (Phase 16) — jamais depuis une URL publique,
 * uniquement depuis le catch-all après résolution d'un chemin via
 * `resolvePath` (`entity_id`, jamais un slug ni un `public_id`). Même
 * patron que `getCompany` ci-dessus.
 */
export const getCompanyById = cache(async function getCompanyById(
  country: string,
  id: number,
): Promise<Company | null> {
  try {
    const { data } = await apiFetch<{ data: Company }>(
      country,
      `/companies/lookup/${id}`,
    );

    return data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }

    throw error;
  }
});
