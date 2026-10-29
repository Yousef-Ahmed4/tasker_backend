<?php

namespace App\Http\Requests\Api\V1\TaskType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'type'       => ['required', 'string', Rule::in(['network', 'device', 'focus', 'other'])],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ];
    }
}
