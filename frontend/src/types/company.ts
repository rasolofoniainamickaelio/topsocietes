/** Miroir de `App\Http\Api\V1\Resources\CompanyResource` (backend). */
export interface Company {
  slug: string;
  public_id: string;
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
  /**
   * Absent tant que la page n'a pas encore été évaluée (Phase 18,
   * `SyncCompanyPageRouteJob`) — traiter comme indexable par défaut plutôt
   * que de masquer la page à tort.
   */
  is_indexable?: boolean;
  links?: CompanyLinks;
  /**
   * Chemin définitif de la fiche (Phase 16, `BuildCompanyPathAction`
   * côté backend) — présent dès que la fiche est chargée via
   * `CompanyShowController`/`CompanyShowByIdController`, marqué optionnel
   * ici par cohérence avec `blocks`/`links`/`is_indexable` ci-dessus (même
   * mécanisme d'attribut transitoire côté backend).
   */
  path?: string;
}

/**
 * Miroir de `App\Domain\Seo\Data\InternalLinkData` (backend, Phase 15).
 * `type` correspond à `App\Domain\Seo\Enums\PageType` — les clés attendues
 * dans `params` dépendent de `type` (`slug` pour company/city/district/
 * admin_division, `citySlug`+`activitySlug` pour activity_city, `path`
 * dans `params` pour une page éditoriale fixe — à ne pas confondre avec le
 * champ `path` ci-dessous).
 */
export interface InternalLink {
  type: string;
  label: string;
  params: Record<string, string>;
  /**
   * Chemin déjà construit (Phase 16) pour les types de page qui ont une
   * route servie par le frontend — `null` sinon (CLAUDE.md §6.5, jamais un
   * lien cassé).
   */
  path: string | null;
}

/**
 * Miroir de `App\Domain\Seo\Data\CompanyLinksData` (backend). Un groupe
 * absent (tableau vide / `null`) signifie qu'il n'y a rien de pertinent à
 * proposer, jamais une erreur (CLAUDE.md §6.5).
 */
export interface CompanyLinks {
  sameTradeInCity: InternalLink[];
  nearby: InternalLink[];
  activityInCity: InternalLink | null;
  activityInNeighborCities: InternalLink[];
  department: InternalLink | null;
  region: InternalLink | null;
  country: InternalLink | null;
  relatedActivities: InternalLink[];
  companyCreation: InternalLink | null;
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
