<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAdminDivisionsRequest extends FormRequest
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
            'level' => ['nullable', 'integer', 'min:1'],
            'parent' => ['nullable', 'string', 'max:255'],
        ];
    }
}
