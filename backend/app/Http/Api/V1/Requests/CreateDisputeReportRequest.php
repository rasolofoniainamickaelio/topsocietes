<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDisputeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'field' => ['required', 'string', 'max:100'],
            'current_value' => ['nullable', 'string', 'max:1000'],
            'proposed_value' => ['required', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:2000'],
            'reporter_name' => ['required', 'string', 'max:255'],
            'reporter_email' => ['required', 'email', 'max:255'],
            'reporter_phone' => ['nullable', 'string', 'max:50'],
            // Justificatif optionnel (Phase 22) : validation mime/taille
            // uniquement, jamais d'antivirus (hors périmètre), stocké sur le
            // disque `local` (non public) — jamais exposé par URL directe.
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
