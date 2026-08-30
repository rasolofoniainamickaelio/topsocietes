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

/**
 * URL provisoire : la structure d'URL définitive est figée en Phase 16.
 * Une seule colonne pour l'instant — la mise en page desktop/mobile
 * (colonne pub, accordéons) est explicitement la Phase 06.
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

  return (
    <main className="mx-auto flex max-w-[720px] flex-col gap-[var(--space-block)] px-4 py-8">
      <CompanyIdentityBlock company={company} />
      <CompanyLegalInfoBlock company={company} />
      <CompanyActivityBlock company={company} />
      <CompanySectorBlock company={company} />
      <CompanyAddressBlock company={company} />
      <CompanyAboutBlock company={company} />
      <CompanyContactBlock company={company} />
      <CompanyProximityBlock company={company} />
      <CompanyDisputeBlock country={country.subdomain} slug={company.slug} />
    </main>
  );
}
