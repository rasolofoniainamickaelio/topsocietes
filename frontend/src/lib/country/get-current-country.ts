import { cache } from "react";
import { headers } from "next/headers";
import { redirect } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api/client";
import { resolveSubdomain } from "@/lib/country/resolve-subdomain";
import type { Country } from "@/types/country";

/**
 * Résout le pays courant depuis l'en-tête Host de la requête en cours.
 * Mémoïsée par requête (React `cache`) : appelable depuis plusieurs
 * Server Components sans multiplier les appels à l'API.
 *
 * Domaine racine, sous-domaine inconnu ou pays désactivé → redirection
 * vers le pays par défaut (jamais de 404 ici, décision produit). Ne
 * retourne donc jamais `null` : soit un pays valide, soit une
 * redirection (qui interrompt le rendu).
 */
export const getCurrentCountry = cache(async (): Promise<Country> => {
  const baseDomain = process.env.NEXT_PUBLIC_BASE_DOMAIN;
  const defaultCountry = process.env.NEXT_PUBLIC_SITE_DEFAULT_COUNTRY;

  if (!baseDomain || !defaultCountry) {
    throw new Error(
      "NEXT_PUBLIC_BASE_DOMAIN et NEXT_PUBLIC_SITE_DEFAULT_COUNTRY doivent être définis",
    );
  }

  const host = (await headers()).get("host");
  const subdomain = resolveSubdomain(host, baseDomain);

  if (!subdomain) {
    redirect(`https://${defaultCountry}.${baseDomain}`);
  }

  try {
    const { data } = await apiFetch<{ data: Country }>(subdomain, "/");

    return data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      redirect(`https://${defaultCountry}.${baseDomain}`);
    }

    throw error;
  }
});
