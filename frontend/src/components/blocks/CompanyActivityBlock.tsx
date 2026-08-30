import { SectionCard } from "@/components/ui/SectionCard";
import type { Company } from "@/types/company";

export function CompanyActivityBlock({ company }: { company: Company }) {
  if (!company.activity) {
    return null;
  }

  return (
    <SectionCard theme="company" title="Activité">
      <p>{company.activity.label}</p>
    </SectionCard>
  );
}
