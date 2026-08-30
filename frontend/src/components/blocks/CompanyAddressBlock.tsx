import { SectionCard } from "@/components/ui/SectionCard";
import type { Company } from "@/types/company";

export function CompanyAddressBlock({ company }: { company: Company }) {
  const establishment = company.main_establishment;

  if (!establishment) {
    return null;
  }

  const streetLine = [establishment.street_number, establishment.street_name]
    .filter(Boolean)
    .join(" ");

  return (
    <SectionCard theme="company" title="Adresse">
      <address className="not-italic">
        {streetLine && <p>{streetLine}</p>}
        {establishment.address_line2 && <p>{establishment.address_line2}</p>}
        {(establishment.postal_code || company.city) && (
          <p>
            {[establishment.postal_code, company.city?.name]
              .filter(Boolean)
              .join(" ")}
          </p>
        )}
      </address>
    </SectionCard>
  );
}
