"use client";

import dynamic from "next/dynamic";
import { FactPill } from "@/components/ui/FactPill";
import { SectionCard } from "@/components/ui/SectionCard";
import type { Company } from "@/types/company";

// Leaflet référence `window` au chargement du module : chargement client
// uniquement, jamais lors du rendu serveur.
const CompanyLeafletMap = dynamic(
  () => import("@/components/blocks/CompanyLeafletMap").then((m) => m.CompanyLeafletMap),
  { ssr: false },
);

function formatDistance(meters: number): string {
  return meters >= 1000 ? `${(meters / 1000).toFixed(1)} km` : `${meters} m`;
}

/**
 * "Carte proximité" (CLAUDE.md §5, maquette) : la carte et l'environnement
 * local forment un seul bloc visuel, les distances alignées à gauche en
 * `FactPill` composent la "règle graduée" décrite dans le système de
 * design.
 */
export function CompanyProximityBlock({ company }: { company: Company }) {
  const lat = company.latitude ? Number(company.latitude) : null;
  const lng = company.longitude ? Number(company.longitude) : null;
  const pois = company.nearby_pois;

  if (lat === null || lng === null) {
    return null;
  }

  return (
    <SectionCard theme="nearby" title="Carte et environnement local">
      <CompanyLeafletMap latitude={lat} longitude={lng} />
      {pois.length > 0 && (
        <ul className="mt-4 flex flex-col gap-2">
          {pois.map((poi) => (
            <li key={`${poi.name}-${poi.distance_m}`} className="flex items-center gap-3 text-sm">
              <FactPill tone="nearby">{formatDistance(poi.distance_m)}</FactPill>
              <span>{poi.name}</span>
            </li>
          ))}
        </ul>
      )}
    </SectionCard>
  );
}
