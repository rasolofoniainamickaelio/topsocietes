import { SectionCard } from "@/components/ui/SectionCard";
import type { CityDistrict } from "@/types/city-page";

/**
 * Quartiers de la ville (Phase 13) — texte simple, pas de lien : les pages
 * quartier n'existent pas encore (CLAUDE.md §6.5, jamais un lien cassé).
 */
export function DistrictsList({ districts }: { districts: CityDistrict[] }) {
  if (districts.length === 0) {
    return null;
  }

  return (
    <SectionCard theme="district" title="Quartiers">
      <ul className="flex flex-wrap gap-2">
        {districts.map((district) => (
          <li
            key={district.slug}
            className="rounded-[var(--radius-chip)] px-3 py-1 text-sm"
            style={{
              backgroundColor: "color-mix(in srgb, var(--t-district) 12%, var(--paper))",
              color: "var(--t-district)",
            }}
          >
            {district.name}
          </li>
        ))}
      </ul>
    </SectionCard>
  );
}
