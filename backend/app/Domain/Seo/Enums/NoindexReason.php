<?php

declare(strict_types=1);

namespace App\Domain\Seo\Enums;

enum NoindexReason: string
{
    case BelowPublicationThreshold = 'below_publication_threshold';
    case ThinContent = 'thin_content';
    case Duplicate = 'duplicate';
    case Unpublished = 'unpublished';
    case Manual = 'manual';
}
