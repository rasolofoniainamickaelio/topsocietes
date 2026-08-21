<?php

declare(strict_types=1);

namespace App\Domain\Company\Enums;

enum CompanyClaimStatus: string
{
    case Pending = 'pending';
    case Verifying = 'verifying';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
