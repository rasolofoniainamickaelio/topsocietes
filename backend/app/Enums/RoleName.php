<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case ContentManager = 'content_manager';
    case CompanyOwner = 'company_owner';
}
