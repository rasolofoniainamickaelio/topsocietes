import type { ContentBlock } from "@/types/company";

/** Miroir (partiel — seuls les champs consommés) de `CityResource`/`ActivityResource` (backend). */
export interface ActivityCityCityRef {
  slug: string;
  name: string;
}

export interface ActivityCityActivityRef {
  slug: string;
  label: string;
}

export interface NeighborCityLink {
  slug: string;
  name: string;
  path: string;
}

/** Miroir de `App\Http\Api\V1\Resources\ActivityCityResource` (backend, Phase 12). */
export interface ActivityCityPage {
  city: ActivityCityCityRef;
  activity: ActivityCityActivityRef;
  path: string;
  is_indexable: boolean;
  blocks: ContentBlock[];
  neighbor_cities: NeighborCityLink[];
}
