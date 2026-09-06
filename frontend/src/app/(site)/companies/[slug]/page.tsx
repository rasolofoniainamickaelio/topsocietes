import type { Metadata } from "next";
import { headers } from "next/headers";
import { notFound } from "next/navigation";
import { getCurrentCountry } from "@/lib/country/get-current-country";
import { getCompany } from "@/lib/api/company";
import { CompanyIdentityBlock } from "@/components/blocks/CompanyIdentityBlock";
import { CompanyLegalInfoBlock } from "@/components/blocks/CompanyLegalInfoBlock";
import { CompanyActivityBlock } from "@/components/blocks/CompanyActivityBlock";
import { CompanySectorBlock } from "@/components/blocks/CompanySectorBlock";
import { CompanyAddressBlock } from "@/components/blocks/CompanyAddressBlock";
import { CompanyAboutBlock } from "@/components/blocks/CompanyAboutBlock";
import { CompanyContactBlock } from "@/components/blocks/CompanyContactBlock";
import { CompanyProximityBlock } from "@/components/blocks/CompanyProximityBlock";
import { CompanyDisputeBlock } from "@/components/blocks/CompanyDisputeBlock";
import { ContentBlock } from "@/components/blocks/ContentBlock";
import { AdSlot } from "@/components/ui/AdSlot";

const AD_CLOSE_COMPANY = "Fermer une société en difficultés";
const AD_CREATE_COMPANY = "Créer gratuitement une société";

/**
 * Canonical AUTO-RÉFÉRENT sur l'URL réellement servie (`/companies/{slug}`),
 * jamais sur `path` (Phase 16, backend) : cette structure d'URL définitive
 * n'est pas encore servie par le frontend, y renvoyer produirait un
 * canonical cassé. `noindex` uniquement si `is_indexable` vaut
 * explicitement `false` — son absence (page jamais évaluée) reste
 * indexable par défaut (Phase 18).
 */
export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const country = await getCurrentCountry();
  const company = await getCompany(country.subdomain, slug);

  if (!company) {
    return {};
  }

  const host = (await headers()).get("host");

  return {
    title: company.legal_name,
    alternates: host ? { canonical: `https://${host}/companies/${company.slug}` } : undefined,
    robots: company.is_indexable === false ? { index: false, follow: false } : undefined,
  };
}

/**
 * URL provisoire : la structure d'URL définitive est figée en Phase 16.
 *
 * Mise en page Phase 06 (CLAUDE.md §5) : colonne principale (max 720px) +
 * rail publicitaire sticky à droite dès 1024px ; sur mobile, les deux
 * publicités se replacent dans le flux (après Contact, puis après les
 * blocs territoriaux) plutôt que dans une colonne latérale. Chaque
 * publicité n'est rendue qu'une fois visuellement : la duplication de
 * balisage entre rail et flux mobile est purement du CSS responsive
 * (`lg:hidden` / `hidden lg:block`), pas de logique conditionnelle JS.
 */
export default async function CompanyPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const country = await getCurrentCountry();
  const company = await getCompany(country.subdomain, slug);

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
