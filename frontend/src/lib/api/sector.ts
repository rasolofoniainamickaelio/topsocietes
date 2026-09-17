import { cache } from "react";
import { apiFetch, ApiError } from "@/lib/api/client";
import type { SectorPage } from "@/types/sector-page";

/**
 * `null` sur 404 — laisse l'appelant décider (typiquement `notFound()`).
 * Mémoïsée par requête (React `cache()`, même patron que `getCity`).
 */
export const getSector = cache(async function getSector(
  country: string,
  slug: string,
): Promise<SectorPage | null> {
  try {
    const { data } = await apiFetch<{ data: SectorPage }>(country, `/sectors/${slug}`);

    return data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }

    throw error;
  }
});
