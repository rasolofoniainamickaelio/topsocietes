import type { Metadata } from "next";
import { headers } from "next/headers";
import { notFound, permanentRedirect } from "next/navigation";
import { getCurrentCountry } from "@/lib/country/get-current-country";
import { getCompanyById } from "@/lib/api/company";
import { resolvePath } from "@/lib/api/routing";
import { CompanyIdentityBlock } from "@/components/blocks/CompanyIdentityBlock";
import { CompanyLegalInfoBlock } from "@/components/blocks/CompanyLegalInfoBlock";
import { CompanyActivityBlock } from "@/components/blocks/CompanyActivityBlock";
import { CompanySectorBlock } from "@/components/blocks/CompanySectorBlock";
import { CompanyAddressBlock } from "@/components/blocks/CompanyAddressBlock";
import { CompanyAboutBlock } from "@/components/blocks/CompanyAboutBlock";
import { CompanyContactBlock } from "@/components/blocks/CompanyContactBlock";
import { CompanyProximityBlock } from "@/components/blocks/CompanyProximityBlock";
import { CompanyUsefulLinksBlock } from "@/components/blocks/CompanyUsefulLinksBlock";
import { CompanyDisputeBlock } from "@/components/blocks/CompanyDisputeBlock";
import { ContentBlock } from "@/components/blocks/ContentBlock";
import { AdSlot } from "@/components/ui/AdSlot";
import type { Country } from "@/types/country";
import type { ResolvedPath } from "@/types/routing";

const AD_CLOSE_COMPANY = "Fermer une société en difficultés";
const AD_CREATE_COMPANY = "Créer gratuitement une société";

/**
 * Résolveur générique (Phase 16) : jamais de pattern d'URL codé en dur ici
 * (CLAUDE.md §6.6) — `/resolve` fait une correspondance exacte sur le
 * chemin demandé contre `routes`/`redirects`, quel que soit le nombre de
 * segments que `countries.url_patterns` définit pour ce pays. Une
 * redirection 410 (Gone) n'a pas d'équivalent natif dans l'App Router côté
 * Server Component (il faudrait un Route Handler dédié pour un statut HTTP
 * arbitraire) — simplification assumée : traitée comme un 404.
 */
async function resolve(path: string): Promise<{ country: Country; resolved: ResolvedPath } | null> {
  const country = await getCurrentCountry();
  const resolved = await resolvePath(country.subdomain, path);

  if (!resolved) {
    return null;
  }

  if (resolved.type === "redirect") {
    if (resolved.status_code === 301) {
      permanentRedirect(resolved.to);
    }

    return null;
  }

  return { country, resolved };
}

/**
 * Canonical AUTO-RÉFÉRENT sur le chemin réellement demandé (Phase 16) :
 * cette page n'est atteinte que si `/resolve` a fait correspondre ce
 * chemin exact à une route, donc il est par construction le chemin
 * définitif. `noindex` uniquement si `is_indexable` vaut explicitement
 * `false` (résultat de `/resolve`, Phase 18) — son absence reste indexable
 * par défaut.
 */
export async function generateMetadata({
  params,
}: {
  params: Promise<{ path: string[] }>;
}): Promise<Metadata> {
  const { path } = await params;
  const requestPath = `/${path.join("/")}`;
  const match = await resolve(requestPath);

  if (!match || match.resolved.type !== "route" || match.resolved.page_type !== "company") {
    return {};
  }

  const company = await getCompanyById(match.country.subdomain, match.resolved.entity_id);

  if (!company) {
    return {};
  }

  const host = (await headers()).get("host");

  return {
    title: company.legal_name,
    alternates: host ? { canonical: `https://${host}${requestPath}` } : undefined,
    robots: match.resolved.is_indexable === false ? { index: false, follow: false } : undefined,
  };
}

/**
 * Mise en page Phase 06 (CLAUDE.md §5) : colonne principale (max 720px) +
 * rail publicitaire sticky à droite dès 1024px ; sur mobile, les deux
 * publicités se replacent dans le flux (après Contact, puis après les
 * blocs territoriaux) plutôt que dans une colonne latérale. Chaque
 * publicité n'est rendue qu'une fois visuellement : la duplication de
 * balisage entre rail et flux mobile est purement du CSS responsive
 * (`lg:hidden` / `hidden lg:block`), pas de logique conditionnelle JS.
 *
 * Seul `page_type === "company"` rend quelque chose pour l'instant : les
 * 5 autres types de page (ville, activité, etc.) n'ont pas encore de page
 * frontend — `notFound()` plutôt qu'une erreur de rendu, à étendre au fur
 * et à mesure que ces pages seront construites.
 */
export default async function ResolvedPage({
  params,
}: {
  params: Promise<{ path: string[] }>;
}) {
  const { path } = await params;
  const requestPath = `/${path.join("/")}`;
  const match = await resolve(requestPath);

  if (!match || match.resolved.type !== "route" || match.resolved.page_type !== "company") {
    notFound();
  }

  const { country, resolved } = match;
  const company = await getCompanyById(country.subdomain, resolved.entity_id);

  if (!company) {
    notFound();
  }

  const contentBlocks = company.blocks ?? [];

  return (
    <div className="mx-auto grid max-w-[1056px] grid-cols-1 gap-[var(--space-block)] px-4 py-8 lg:grid-cols-[720px_300px]">
      <main className="flex flex-col gap-[var(--space-block)]">
        <CompanyIdentityBlock company={company} />
        <CompanyLegalInfoBlock company={company} country={country} />
        <CompanyActivityBlock company={company} />
        <CompanySectorBlock company={company} />
        <CompanyAddressBlock company={company} />
        <CompanyAboutBlock company={company} />
        <CompanyContactBlock company={company} />

        <AdSlot label={AD_CLOSE_COMPANY} className="lg:hidden" />

        <CompanyProximityBlock company={company} />

        {contentBlocks.map((block, index) => (
          <ContentBlock key={`${block.type}-${index}`} block={block} />
        ))}

        <CompanyUsefulLinksBlock company={company} />

        <AdSlot label={AD_CREATE_COMPANY} className="lg:hidden" />

        <CompanyDisputeBlock country={country.subdomain} slug={company.slug} />
      </main>

      <aside className="hidden flex-col gap-[var(--space-block)] lg:flex">
        <div className="sticky top-6 flex flex-col gap-[var(--space-block)]">
          <AdSlot label={AD_CLOSE_COMPANY} />
          <AdSlot label={AD_CREATE_COMPANY} />
        </div>
      </aside>
    </div>
  );
}
