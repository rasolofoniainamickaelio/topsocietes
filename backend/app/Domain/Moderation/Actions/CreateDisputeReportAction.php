<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Actions;

use App\Domain\Company\Models\Company;
use App\Domain\Moderation\Data\CreateDisputeReportData;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeReport;

/**
 * Jamais l'IP en clair : seul son hash est conservé, anti-abus sans donnée
 * personnelle brute (docs/DATABASE.md §4-K, ADR 0002).
 */
class CreateDisputeReportAction
{
    public function execute(Company $company, CreateDisputeReportData $data, string $ipHash): DisputeReport
    {
        return DisputeReport::query()->create([
            'company_id' => $company->id,
            'field' => $data->field,
            'current_value' => $data->current_value,
            'proposed_value' => $data->proposed_value,
            'reason' => $data->reason,
            'reporter_name' => $data->reporter_name,
            'reporter_email' => $data->reporter_email,
            'reporter_phone' => $data->reporter_phone,
            'status' => DisputeStatus::Submitted,
            'ip_hash' => $ipHash,
        ]);
    }
}
