<?php

declare(strict_types=1);

namespace App\Domain\Company\Enums;

enum ContactVisibility: string
{
    case Hidden = 'hidden';
    case Visible = 'visible';
    case ForcedVisible = 'forced_visible';
    case ForcedHidden = 'forced_hidden';
}
