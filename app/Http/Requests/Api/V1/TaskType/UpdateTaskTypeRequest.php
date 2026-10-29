<?php

namespace App\Http\Requests\Api\V1\TaskType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'required', 'string', 'max:255'],
            'type'       => ['sometimes', 'required', 'string', Rule::in(['network', 'device', 'focus', 'other'])],
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
        ];
    }
}
