<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Actions;

use App\Domain\Company\Models\Company;
use App\Domain\Moderation\Data\CreateDisputeReportData;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeEvent;
use App\Domain\Moderation\Models\DisputeReport;
use App\Domain\Moderation\Notifications\DisputeSubmittedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Jamais l'IP en clair : seul son hash est conservé, anti-abus sans donnée
 * personnelle brute (docs/DATABASE.md §4-K, ADR 0002).
 */
class CreateDisputeReportAction
{
    public function execute(Company $company, CreateDisputeReportData $data, string $ipHash): DisputeReport
    {
        $dispute = DisputeReport::query()->create([
            'company_id' => $company->id,
            'field' => $data->field,
            'current_value' => $data->current_value,
            'proposed_value' => $data->proposed_value,
            'reason' => $data->reason,
            'reporter_name' => $data->reporter_name,
            'reporter_email' => $data->reporter_email,
            'reporter_phone' => $data->reporter_phone,
            'evidence_path' => $data->evidence_path,
            'status' => DisputeStatus::Submitted,
            'ip_hash' => $ipHash,
        ]);

        DisputeEvent::create([
            'dispute_id' => $dispute->id,
            'user_id' => null,
            'action' => DisputeStatus::Submitted->value,
        ]);

        Notification::route('mail', $dispute->reporter_email)
            ->notify(new DisputeSubmittedNotification($dispute));

        return $dispute;
    }
}
