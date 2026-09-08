import { cache } from "react";
import { apiFetch, ApiError } from "@/lib/api/client";
import type { ResolvedPath } from "@/types/routing";

/**
 * `null` quand ni redirection ni route n'existe pour ce chemin — laisse
 * l'appelant décider (typiquement `notFound()`). Mémoïsée par requête
 * (React `cache`, même patron que `getCompany`) : `generateMetadata` et le
 * composant de page du catch-all appellent tous deux `resolvePath` pour la
 * même requête sans dupliquer l'appel réseau.
 */
export const resolvePath = cache(async function resolvePath(
  country: string,
  path: string,
): Promise<ResolvedPath | null> {
  try {
    const { data } = await apiFetch<{ data: ResolvedPath }>(
      country,
      `/resolve?path=${encodeURIComponent(path)}`,
    );

    return data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }

    throw error;
  }
});
