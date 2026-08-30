import { SectionCard } from "@/components/ui/SectionCard";
import type { Company } from "@/types/company";

export function CompanySectorBlock({ company }: { company: Company }) {
  const sectors = company.activity?.sectors ?? [];

  if (sectors.length === 0) {
    return null;
  }

  return (
    <SectionCard theme="sector" title="Secteur">
      <ul className="flex flex-wrap gap-2">
        {sectors.map((sector) => (
          <li
            key={sector.slug}
            className="rounded-[var(--radius-chip)] px-2 py-1 text-sm"
            style={{
              backgroundColor: "color-mix(in srgb, var(--t-sector) 12%, var(--paper))",
              color: "var(--t-sector)",
            }}
          >
            {sector.name}
          </li>
        ))}
      </ul>
    </SectionCard>
  );
}
