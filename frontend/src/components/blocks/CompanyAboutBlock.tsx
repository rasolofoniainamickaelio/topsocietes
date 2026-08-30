import { SectionCard } from "@/components/ui/SectionCard";
import type { Company } from "@/types/company";

export function CompanyAboutBlock({ company }: { company: Company }) {
  if (!company.about_text) {
    return null;
  }

  return (
    <SectionCard theme="company" title="À propos">
      <p className="whitespace-pre-line">{company.about_text}</p>
    </SectionCard>
  );
}
