<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'update' => [
                'name' => ['sometimes', 'required', 'string', 'regex:/^[\p{L}\p{N}]{3,30}$/u'],
                'email' => ['sometimes', 'required', 'string', 'email', 'max:30', Rule::unique('users')->ignore($this->user()->id)],
                'current_password' => ['bail', Rule::requiredIf($this->has('email') && $this->input('email') !== $this->user()->email), 'nullable', 'string', 'current_password:web'],
            ],
            'password' => ['current_password' => ['bail', 'required', 'string', 'current_password:web'], 'password' => ['required', 'string', 'min:5', 'max:255', 'confirmed', 'different:current_password']],
            'preferences' => ['email_notifications' => ['sometimes', 'boolean'], 'in_app_notifications' => ['sometimes', 'boolean'], 'reminder_days' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:365'], 'appearance' => ['sometimes', Rule::in(['light', 'dark', 'system'])]],
            'destroy' => ['confirmed' => ['required', 'accepted'], 'current_password' => ['bail', 'required', 'string', 'current_password:web']],
            default => [],
        };
    }
}
