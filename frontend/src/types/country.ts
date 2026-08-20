/** Miroir de `App\Http\Api\V1\Resources\CountryResource` (backend). */
export interface Country {
  code: string;
  name: string;
  subdomain: string;
  default_locale: string;
  currency: string;
  timezone: string;
  admin_level_labels: Record<string, string> | null;
  url_patterns: Record<string, string> | null;
}
