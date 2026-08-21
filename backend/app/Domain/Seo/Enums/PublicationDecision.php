<?php

declare(strict_types=1);

namespace App\Domain\Seo\Enums;

enum PublicationDecision: string
{
    case Publish = 'publish';
    case Noindex = 'noindex';
    case Skip = 'skip';
}
