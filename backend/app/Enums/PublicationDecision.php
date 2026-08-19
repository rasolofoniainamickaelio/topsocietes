<?php

declare(strict_types=1);

namespace App\Enums;

enum PublicationDecision: string
{
    case Publish = 'publish';
    case Noindex = 'noindex';
    case Skip = 'skip';
}
