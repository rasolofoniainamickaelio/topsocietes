import type { ContentBlock } from "@/types/company";
import type { InternalLink } from "@/types/city-page";

/** Miroir de `App\Domain\Seo\Data\SectorTerritoryLinksData` (backend, Phase 13). */
export interface SectorTerritoryLinks {
  activities: InternalLink[];
}

/** Miroir de `App\Http\Api\V1\Resources\SectorResource` (backend), étendu Phase 13. */
export interface SectorPage {
  slug: string;
  name: string;
  companies_count: number | null;
  blocks: ContentBlock[];
  path?: string;
  is_indexable?: boolean;
  links?: SectorTerritoryLinks;
}
