/** Un identifiant officiel d'entreprise pour un pays (ex. SIREN, ICE, BCE). */
export interface CountryIdentifier {
  name: string;
  pattern?: string;
}

/** Miroir de `App\Http\Api\V1\Resources\CountryResource` (backend). */
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
}
