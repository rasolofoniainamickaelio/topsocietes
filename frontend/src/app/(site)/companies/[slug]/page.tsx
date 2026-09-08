import { notFound, permanentRedirect } from "next/navigation";
import { getCurrentCountry } from "@/lib/country/get-current-country";
import { getCompany } from "@/lib/api/company";

/**
 * Ancienne URL provisoire (avant Phase 16) — conservée comme redirecteur
 * permanent plutôt que supprimée, pour ne jamais casser un lien déjà
 * partagé. La fiche elle-même est désormais rendue par le catch-all
 * (`app/(site)/[...path]/page.tsx`), sur l'URL définitive
 * `company.path` (`BuildCompanyPathAction`, backend).
 */
export default async function LegacyCompanyPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const country = await getCurrentCountry();
  const company = await getCompany(country.subdomain, slug);

  if (!company || !company.path) {
    notFound();
  }

  permanentRedirect(company.path);
}
