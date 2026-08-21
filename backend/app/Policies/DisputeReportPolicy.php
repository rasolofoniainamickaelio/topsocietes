<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\DisputeReport;
use App\Models\User;

class DisputeReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::DisputesView->value);
    }

    public function view(User $user, DisputeReport $dispute): bool
    {
        return $user->can(PermissionName::DisputesView->value);
    }

    public function review(User $user, DisputeReport $dispute): bool
    {
        return $user->can(PermissionName::DisputesReview->value);
    }
}
