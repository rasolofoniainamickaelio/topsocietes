import { NextResponse, type NextRequest } from "next/server";
import { resolveSubdomain } from "@/lib/country/resolve-subdomain";

/**
 * Garde-fou préprod (Phase 18, volet applicatif) : même défaut que
 * `config('app.env')` côté backend (`env('APP_ENV', 'production')`) — un
 * `APP_ENV` absent vaut production, pour que les deux volets applicatifs
 * (ici et `RobotsController`) restent cohérents entre eux. Le volet infra
 * (auth HTTP sur le vhost préprod) reste à faire côté serveur (CLAUDE.md
 * §9, hors périmètre ici).
 */
function isNonProduction(): boolean {
  const appEnv = process.env.APP_ENV;

  return appEnv !== undefined && appEnv !== "production";
}

/**
 * Rôles : rediriger le domaine racine / `www` vers le pays par défaut
 * avant tout rendu (aucune validation de sous-domaine ici — pas d'appel
 * réseau à chaque requête — un sous-domaine inconnu ou désactivé passe et
 * est traité une seule fois côté Server Component via `getCurrentCountry`,
 * qui redirige aussi vers le pays par défaut) ; et poser `X-Robots-Tag`
 * hors production, sur toute réponse.
 */
export function middleware(request: NextRequest): NextResponse {
  const baseDomain = process.env.NEXT_PUBLIC_BASE_DOMAIN;
  const defaultCountry = process.env.NEXT_PUBLIC_SITE_DEFAULT_COUNTRY;

  let response: NextResponse;

  if (!baseDomain || !defaultCountry) {
    response = NextResponse.next();
  } else {
    const host = request.headers.get("host");
    const subdomain = resolveSubdomain(host, baseDomain);

    if (subdomain === null) {
      const redirectUrl = new URL(request.nextUrl.pathname + request.nextUrl.search, request.url);
      redirectUrl.host = `${defaultCountry}.${baseDomain}`;

      response = NextResponse.redirect(redirectUrl, 307);
    } else {
      response = NextResponse.next();
    }
  }

  if (isNonProduction()) {
    response.headers.set("X-Robots-Tag", "noindex, nofollow");
  }

  return response;
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico).*)"],
};
