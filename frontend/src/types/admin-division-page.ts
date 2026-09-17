import type { ContentBlock } from "@/types/company";
import type { InternalLink } from "@/types/city-page";

/** Miroir de `App\Domain\Seo\Data\AdminDivisionTerritoryLinksData` (backend, Phase 13). */
export interface AdminDivisionTerritoryLinks {
  parent: InternalLink;
  children: InternalLink[];
}

/** Miroir de `App\Http\Api\V1\Resources\AdminDivisionResource` (backend), étendu Phase 13. */
export interface AdminDivisionPage {
  slug: string;
  name: string;
  level: number;
  population: number | null;
  companies_count: number | null;
  parent: { slug: string; name: string } | null;
  children: { slug: string; name: string }[];
  blocks: ContentBlock[];
  path?: string;
  level_label?: string | null;
  is_indexable?: boolean;
  links?: AdminDivisionTerritoryLinks;
}
