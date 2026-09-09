import type { ContentBlock } from "@/types/company";

/** Miroir de `App\Http\Api\V1\Resources\DistrictResource` (backend). */
export interface CityDistrict {
  slug: string;
  name: string;
}

/** Miroir de `App\Http\Api\V1\Resources\CityNeighborResource` (backend). */
export interface CityNeighbor {
  slug: string;
  name: string;
  distance_m: number;
}

export interface InternalLink {
  type: string;
  label: string;
  params: Record<string, string>;
  path: string | null;
}

/** Miroir de `App\Domain\Seo\Data\CityTerritoryLinksData` (backend, Phase 13). */
export interface CityTerritoryLinks {
  country: InternalLink;
  activities: InternalLink[];
}

/** Miroir de `App\Http\Api\V1\Resources\CityResource` (backend), étendu Phase 13. */
export interface CityPage {
  slug: string;
  name: string;
  population: number | null;
  area_km2: string | null;
  companies_count: number | null;
  has_local_content: boolean;
  latitude: string | null;
  longitude: string | null;
  districts: CityDistrict[];
  neighbors: CityNeighbor[];
  blocks: ContentBlock[];
  admin_division: { name: string; region: string | null } | null;
  path?: string;
  is_indexable?: boolean;
  links?: CityTerritoryLinks;
}
