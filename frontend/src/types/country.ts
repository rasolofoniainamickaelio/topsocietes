import type { InternalLink } from "@/types/city-page";

/** Un identifiant officiel d'entreprise pour un pays (ex. SIREN, ICE, BCE). */
export interface CountryIdentifier {
  name: string;
  pattern?: string;
}

/** Miroir de `App\Domain\Seo\Data\CountryTerritoryLinksData` (backend, Phase 13). */
export interface CountryTerritoryLinks {
  regions: InternalLink[];
  activities: InternalLink[];
}

/** Miroir de `App\Http\Api\V1\Resources\CountryResource` (backend), étendu Phase 13. */
export interface Country {
  code: string;
  name: string;
  subdomain: string;
  default_locale: string;
  currency: string;
  timezone: string;
  admin_level_labels: Record<string, string> | null;
  identifier_config: {
    primary?: CountryIdentifier;
    establishment?: CountryIdentifier;
  } | null;
  url_patterns: Record<string, string> | null;
  path?: string;
  is_indexable?: boolean;
  links?: CountryTerritoryLinks;
}
