import { NextResponse, type NextRequest } from "next/server";
import { resolveSubdomain } from "@/lib/country/resolve-subdomain";

/**
 * Seul rôle : rediriger le domaine racine / `www` vers le pays par
 * défaut, avant tout rendu. Aucune validation de sous-domaine ici (pas
 * d'appel réseau à chaque requête) — un sous-domaine inconnu ou
 * désactivé passe et est traité une seule fois, côté Server Component
 * (`getCurrentCountry`), qui redirige aussi vers le pays par défaut.
 */
export function middleware(request: NextRequest): NextResponse {
  const baseDomain = process.env.NEXT_PUBLIC_BASE_DOMAIN;
  const defaultCountry = process.env.NEXT_PUBLIC_SITE_DEFAULT_COUNTRY;

  if (!baseDomain || !defaultCountry) {
    return NextResponse.next();
  }

  const host = request.headers.get("host");
  const subdomain = resolveSubdomain(host, baseDomain);

  if (subdomain === null) {
    const redirectUrl = new URL(request.nextUrl.pathname + request.nextUrl.search, request.url);
    redirectUrl.host = `${defaultCountry}.${baseDomain}`;

    return NextResponse.redirect(redirectUrl, 307);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico).*)"],
};
