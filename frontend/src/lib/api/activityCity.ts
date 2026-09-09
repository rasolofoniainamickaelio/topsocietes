import { cache } from "react";
import { apiFetch, ApiError } from "@/lib/api/client";
import type { ActivityCityPage } from "@/types/activity-city";

/**
 * `null` sur 404 — laisse l'appelant décider (typiquement `notFound()`).
 * Mémoïsée par requête (React `cache`, même patron que `getCompany`).
 */
export const getActivityCity = cache(async function getActivityCity(
  country: string,
  citySlug: string,
  activitySlug: string,
): Promise<ActivityCityPage | null> {
  try {
    const { data } = await apiFetch<{ data: ActivityCityPage }>(
      country,
      `/activity-city/${citySlug}/${activitySlug}`,
    );

    return data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }

    throw error;
  }
});
