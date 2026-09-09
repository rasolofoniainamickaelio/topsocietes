import { cache } from "react";
import { apiFetch, ApiError } from "@/lib/api/client";
import type { CityPage } from "@/types/city-page";

/**
 * `null` sur 404 — laisse l'appelant décider (typiquement `notFound()`).
 * Mémoïsée par requête (React `cache`, même patron que `getCompany`).
 */
export const getCity = cache(async function getCity(
  country: string,
  slug: string,
): Promise<CityPage | null> {
  try {
    const { data } = await apiFetch<{ data: CityPage }>(country, `/cities/${slug}`);

    return data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }

    throw error;
  }
});
