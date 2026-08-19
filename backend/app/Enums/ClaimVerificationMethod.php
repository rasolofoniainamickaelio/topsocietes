<?php

declare(strict_types=1);

namespace App\Enums;

enum ClaimVerificationMethod: string
{
    case EmailDomain = 'email_domain';
    case PhoneCallback = 'phone_callback';
    case Document = 'document';
    case Manual = 'manual';
}
