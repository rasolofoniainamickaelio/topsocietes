<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Requests;

use App\Domain\Company\Enums\ClaimVerificationMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitCompanyClaimRequest extends FormRequest
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
            'verification_method' => ['required', Rule::enum(ClaimVerificationMethod::class)],
            'evidence_path' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
