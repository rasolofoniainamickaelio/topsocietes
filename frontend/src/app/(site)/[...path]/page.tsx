import type { Metadata } from "next";
import { headers } from "next/headers";
import { notFound, permanentRedirect } from "next/navigation";
import { getCurrentCountry } from "@/lib/country/get-current-country";
import { getCompanyById } from "@/lib/api/company";
import { getActivityCity } from "@/lib/api/activityCity";
import { getCity } from "@/lib/api/city";
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
import { ActivityCityHeader } from "@/components/activity-city/ActivityCityHeader";
import { CityActivityContentBlock } from "@/components/activity-city/CityActivityContentBlock";
import { CompanyListSection } from "@/components/activity-city/CompanyListSection";
import { NeighborCitiesList } from "@/components/activity-city/NeighborCitiesList";
import { CityHeader } from "@/components/city/CityHeader";
import { CityActivitiesList } from "@/components/city/CityActivitiesList";
import { DistrictsList } from "@/components/city/DistrictsList";
import type { Country } from "@/types/country";
import type { ResolvedPath } from "@/types/routing";

const AD_CLOSE_COMPANY = "Fermer une société en difficultés";
const AD_CREATE_COMPANY = "Créer gratuitement une société";

type ResolvedRoute = Extract<ResolvedPath, { type: "route" }>;

/**
 * Résolveur générique (Phase 16) : jamais de pattern d'URL codé en dur ici
 * (CLAUDE.md §6.6) — `/resolve` fait une correspondance exacte sur le
 * chemin demandé contre `routes`/`redirects`, quel que soit le nombre de
 * segments que `countries.url_patterns` définit pour ce pays. Une
 * redirection 410 (Gone) n'a pas d'équivalent natif dans l'App Router côté
 * Server Component (il faudrait un Route Handler dédié pour un statut HTTP
 * arbitraire) — simplification assumée : traitée comme un 404. Le type de
 * retour est déjà restreint à la variante `route` : tout appelant peut lire
 * `resolved.page_type`/`is_indexable` sans revérifier `resolved.type`.
 */
async function resolve(path: string): Promise<{ country: Country; resolved: ResolvedRoute } | null> {
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

  if (!match) {
    return {};
  }

  const { country, resolved } = match;
  const host = (await headers()).get("host");
  const robots = resolved.is_indexable === false ? { index: false, follow: false } : undefined;

  if (resolved.page_type === "company") {
    if (resolved.entity_id === null) {
      return {};
    }

    const company = await getCompanyById(country.subdomain, resolved.entity_id);

    if (!company) {
      return {};
    }

    return {
      title: company.legal_name,
      alternates: host ? { canonical: `https://${host}${requestPath}` } : undefined,
      robots,
    };
  }

  if (resolved.page_type === "activity_city" && path.length === 2) {
    const page = await getActivityCity(country.subdomain, path[0], path[1]);

    if (!page) {
      return {};
    }

    return {
      title: `${page.activity.label} à ${page.city.name}`,
      alternates: host ? { canonical: `https://${host}${requestPath}` } : undefined,
      robots,
    };
  }

  if (resolved.page_type === "city" && path.length === 1) {
    const city = await getCity(country.subdomain, path[0]);

    if (!city) {
      return {};
    }

    return {
      title: city.name,
      alternates: host ? { canonical: `https://${host}${requestPath}` } : undefined,
      robots,
    };
  }

  return {};
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
 * `page_type` reconnus : `company` (Phase 05/06/16), `activity_city`
 * (Phase 12) et `city` (Phase 13). Les autres types de page (département,
 * région, pays, activité seule, secteur) n'ont pas encore de page
 * frontend — `notFound()` plutôt qu'une erreur de rendu, à étendre au fur
 * et à mesure que ces pages seront construites (même moule que `city`).
 */
export default async function ResolvedPage({
  params,
  searchParams,
}: {
  params: Promise<{ path: string[] }>;
  searchParams: Promise<{ cursor?: string }>;
}) {
  const { path } = await params;
  const requestPath = `/${path.join("/")}`;
  const match = await resolve(requestPath);

  if (!match) {
    notFound();
  }

  const { country, resolved } = match;

  if (resolved.page_type === "activity_city" && path.length === 2) {
    const page = await getActivityCity(country.subdomain, path[0], path[1]);

    if (!page) {
      notFound();
    }

    const { cursor } = await searchParams;

    return (
      <div className="mx-auto flex max-w-[720px] flex-col gap-[var(--space-block)] px-4 py-8">
        <ActivityCityHeader page={page} />

        {page.blocks.map((block, index) => (
          <CityActivityContentBlock
            key={`${block.type}-${index}`}
            block={block}
            activityLabel={page.activity.label}
            cityName={page.city.name}
          />
        ))}

        <CompanyListSection
          country={country.subdomain}
          pagePath={page.path}
          citySlug={page.city.slug}
          activitySlug={page.activity.slug}
          cursor={cursor}
          cityName={page.city.name}
          activityLabel={page.activity.label}
        />

        <NeighborCitiesList cities={page.neighbor_cities} />
      </div>
    );
  }

  if (resolved.page_type === "city" && path.length === 1) {
    const city = await getCity(country.subdomain, path[0]);

    if (!city) {
      notFound();
    }

    const { cursor } = await searchParams;

    return (
      <div className="mx-auto flex max-w-[720px] flex-col gap-[var(--space-block)] px-4 py-8">
        <CityHeader city={city} countryName={country.name} />

        {city.blocks.map((block, index) => (
          <ContentBlock key={`${block.type}-${index}`} block={block} />
        ))}

        <CompanyListSection
          country={country.subdomain}
          pagePath={city.path ?? requestPath}
          citySlug={city.slug}
          cursor={cursor}
          cityName={city.name}
        />

        <CityActivitiesList activities={city.links?.activities ?? []} />
        <DistrictsList districts={city.districts} />
      </div>
    );
  }

  if (resolved.page_type !== "company" || resolved.entity_id === null) {
    notFound();
  }

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
