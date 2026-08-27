<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCitiesRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
