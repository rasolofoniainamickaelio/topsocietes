<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Catalogue complet des permissions (`docs/adr/0002-access-control.md`).
 * `manage` couvre création/modification/suppression, `view` la lecture seule.
 * Les domaines sans `Policy` dédiée aujourd'hui (aucune Resource Filament
 * ne les consomme encore) sont vérifiés directement via `can()` quand leur
 * Resource sera créée — pas de classe `Policy` vide en attendant.
 */
enum PermissionName: string
{
    case CompaniesView = 'companies.view';
    case CompaniesManage = 'companies.manage';

    case ContactsManage = 'contacts.manage';
    case ContactsViewMasked = 'contacts.view_masked';

    case ClaimsView = 'claims.view';
    case ClaimsReview = 'claims.review';

    case ContentManage = 'content.manage';

    case SourcesManage = 'sources.manage';

    case AiPipelineManage = 'ai_pipeline.manage';

    case ImportsManage = 'imports.manage';

    case GeoManage = 'geo.manage';

    case TaxonomyManage = 'taxonomy.manage';

    case SeoManage = 'seo.manage';

    case DisputesView = 'disputes.view';
    case DisputesReview = 'disputes.review';

    case AdsManage = 'ads.manage';

    case BillingView = 'billing.view';
    case BillingManage = 'billing.manage';

    /** Réservée à super_admin (via le bypass Gate::before, jamais assignée en base). */
    case UsersManage = 'users.manage';
}
