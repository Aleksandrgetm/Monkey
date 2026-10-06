<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'product_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'merchant' => ['sometimes', 'nullable', 'string', 'max:255'],
            'kind' => ['sometimes', 'nullable', Rule::in(['receipt', 'warranty', 'other'])],
            'warranty_status' => ['sometimes', 'nullable', Rule::in(['active', 'expiring', 'expired'])],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', ...($this->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'sort' => ['sometimes', Rule::in(['created_at', 'name', 'amount', 'purchase_date', 'warranty_end_date'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
