import { FactPill } from "@/components/ui/FactPill";
import { SectionCard } from "@/components/ui/SectionCard";
import type { Company } from "@/types/company";
import type { Country } from "@/types/country";

export function CompanyLegalInfoBlock({
  company,
  country,
}: {
  company: Company;
  country: Country;
}) {
  const identifierLabel = country.identifier_config?.primary?.name ?? "Identifiant";

  return (
    <SectionCard theme="company" title="Informations légales">
      <dl className="flex flex-wrap gap-x-6 gap-y-3 text-sm">
        <div>
          <dt className="text-[var(--ink-muted)]">{identifierLabel}</dt>
          <dd className="mt-1">
            <FactPill tone="company">{company.national_id}</FactPill>
          </dd>
        </div>
        {company.legal_form_label && (
          <div>
            <dt className="text-[var(--ink-muted)]">Forme juridique</dt>
            <dd className="mt-1">
              <FactPill tone="company">{company.legal_form_label}</FactPill>
            </dd>
          </div>
        )}
        {company.created_date && (
          <div>
            <dt className="text-[var(--ink-muted)]">Création</dt>
            <dd className="mt-1">
              <FactPill tone="company">{company.created_date}</FactPill>
            </dd>
          </div>
        )}
      </dl>
    </SectionCard>
  );
}
