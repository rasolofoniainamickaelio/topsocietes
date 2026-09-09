import Link from "next/link";
import { SectionCard } from "@/components/ui/SectionCard";
import type { NeighborCityLink } from "@/types/activity-city";

export function NeighborCitiesList({ cities }: { cities: NeighborCityLink[] }) {
  if (cities.length === 0) {
    return null;
  }

  return (
    <SectionCard theme="district" title="Villes voisines">
      <ul className="flex flex-wrap gap-2">
        {cities.map((city) => (
          <li key={city.path}>
            <Link
              href={city.path}
              className="rounded-[var(--radius-chip)] px-3 py-1 text-sm"
              style={{
                backgroundColor: "color-mix(in srgb, var(--t-district) 12%, var(--paper))",
                color: "var(--t-district)",
              }}
            >
              {city.name}
            </Link>
          </li>
        ))}
      </ul>
    </SectionCard>
  );
}
