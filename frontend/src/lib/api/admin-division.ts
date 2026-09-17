import { cache } from "react";
import { apiFetch, ApiError } from "@/lib/api/client";
import type { AdminDivisionPage } from "@/types/admin-division-page";

/**
 * `null` sur 404 — laisse l'appelant décider (typiquement `notFound()`).
 * Mémoïsée par requête (React `cache`, même patron que `getCity`).
 */
export const getAdminDivision = cache(async function getAdminDivision(
  country: string,
  slug: string,
): Promise<AdminDivisionPage | null> {
  try {
    const { data } = await apiFetch<{ data: AdminDivisionPage }>(country, `/admin-divisions/${slug}`);

    return data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }

    throw error;
  }
});
