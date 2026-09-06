/** Miroir de `App\Http\Api\V1\Resources\CompanyResource` (backend). */
export interface Company {
  slug: string;
  national_id: string;
  legal_name: string;
  trade_name: string | null;
  legal_form_code: string | null;
  legal_form_label: string | null;
  status: string;
  created_date: string | null;
  headcount_range: string | null;
  about_text: string | null;
  latitude: string | null;
  longitude: string | null;
  activity: CompanyActivity | null;
  city: CompanyCityRef | null;
  district: CompanyDistrictRef | null;
  main_establishment: Establishment | null;
  nearby_pois: NearbyPoi[];
  contacts?: Contact[];
  blocks?: ContentBlock[];
}

/**
 * Contenu territorial/sectoriel mutualisé (quartier, ville, activité,
 * secteur, croisé ville×activité — Phase 08). `type` correspond aux clés
 * de `App\Domain\Content\Enums\ContentSection` (backend) ; un type non
 * reconnu par le registre front doit être ignoré silencieusement
 * (CLAUDE.md §4), jamais provoquer une erreur de rendu.
 */
export interface ContentBlock {
  type: string;
  data: {
    title: string | null;
    body: string | null;
    [key: string]: unknown;
  };
}

export interface Sector {
  slug: string;
  name: string;
}

export interface CompanyActivity {
  slug: string;
  label: string;
  sectors: Sector[];
}

export interface CompanyCityRef {
  slug: string;
  name: string;
}

export interface CompanyDistrictRef {
  slug: string;
  name: string;
}

export interface Establishment {
  street_number: string | null;
  street_name: string | null;
  address_line2: string | null;
  postal_code: string | null;
  is_headquarters: boolean;
}

export interface NearbyPoi {
  name: string;
  category: string;
  distance_m: number;
}

export interface Contact {
  type: string;
  value: string;
}
