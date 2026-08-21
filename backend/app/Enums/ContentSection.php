<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Clés de section connues, à titre de constantes typées (seeders,
 * réutilisation dans le code applicatif) — PAS le type casté de la
 * colonne `section` sur `city_contents`/`district_contents`/
 * `activity_contents`/`city_activity_contents`. Cette colonne reste un
 * `varchar` : `content_sections` est la table de référence qui rend
 * chaque bloc activable/désactivable sans déploiement de code (prompt
 * Phase 2, Domaine E). Caster `section` sur cet enum romprait cette
 * architecture modulaire à chaque nouvelle section ajoutée en base.
 *
 * Regroupées par table pour lisibilité ; `Faq` est partagée par
 * `activity_contents` et `city_activity_contents`.
 */
enum ContentSection: string
{
    // city_contents / district_contents — thématiques du design system (CLAUDE.md §5)
    case History = 'history';
    case Nature = 'nature';
    case Leisure = 'leisure';
    case Specialty = 'specialty';
    case Stats = 'stats';

    // activity_contents
    case UnderstandingSector = 'understanding_sector';
    case HowItWorks = 'how_it_works';
    case Jobs = 'jobs';
    case Diplomas = 'diplomas';
    case Regulation = 'regulation';
    case CommonMistakes = 'common_mistakes';
    case HowToChoose = 'how_to_choose';
    case BusinessCreation = 'business_creation';

    // city_activity_contents
    case LocalOverview = 'local_overview';
    case LocalHistory = 'local_history';
    case LocalSpecifics = 'local_specifics';
    case LocalEconomy = 'local_economy';

    // partagée
    case Faq = 'faq';
}
