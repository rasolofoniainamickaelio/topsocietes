import type { ContentBlock } from "@/types/company";
import type { InternalLink } from "@/types/city-page";

/** Miroir de `App\Domain\Seo\Data\ActivityTerritoryLinksData` (backend, Phase 13). */
export interface ActivityTerritoryLinks {
  parent: InternalLink | null;
  sectors: InternalLink[];
  cities: InternalLink[];
}

/** Miroir de `App\Http\Api\V1\Resources\ActivityResource` (backend), étendu Phase 13. */
export interface ActivityPage {
  slug: string;
  code: string;
  label: string;
  level: number;
  companies_count: number | null;
  sectors: { slug: string; name: string }[];
  blocks: ContentBlock[];
  path?: string;
  is_indexable?: boolean;
  links?: ActivityTerritoryLinks;
}
