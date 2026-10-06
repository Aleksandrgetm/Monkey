<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'users' => ['search' => ['sometimes', 'nullable', 'string', 'max:255'], 'role' => ['sometimes', 'nullable', Rule::in([0, 1])], 'status' => ['sometimes', 'nullable', Rule::in([0, 1])], 'sort' => ['sometimes', Rule::in(['name', 'email', 'created_at'])], 'direction' => ['sometimes', Rule::in(['asc', 'desc'])], 'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100']],
            'updateUser' => ['name' => ['sometimes', 'required', 'string', 'regex:/^[\p{L}\p{N}]{3,30}$/u'], 'email' => ['sometimes', 'required', 'string', 'email', 'max:30', Rule::unique('users')->ignore($this->route('user')?->id)], 'role' => ['sometimes', 'required', Rule::in([0, 1])], 'status' => ['sometimes', 'required', Rule::in([0, 1])]],
            'updateSettings' => ['registration_enabled' => ['sometimes', 'boolean'], 'reminder_days' => ['sometimes', 'integer', 'min:0', 'max:365']],
            'deleteUser' => ['confirmed' => ['required', 'accepted']],
            default => [],
        };
    }
}
