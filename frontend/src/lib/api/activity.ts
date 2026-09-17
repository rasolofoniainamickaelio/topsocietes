import { cache } from "react";
import { apiFetch, ApiError } from "@/lib/api/client";
import type { ActivityPage } from "@/types/activity-page";

/**
 * `null` sur 404 — laisse l'appelant décider (typiquement `notFound()`).
 * Mémoïsée par requête (React `cache`, même patron que `getCity`).
 */
export const getActivity = cache(async function getActivity(
  country: string,
  slug: string,
): Promise<ActivityPage | null> {
  try {
    const { data } = await apiFetch<{ data: ActivityPage }>(country, `/activities/${slug}`);

    return data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }

    throw error;
  }
});
