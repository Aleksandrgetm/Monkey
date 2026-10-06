<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');
        if ($product !== null) {
            abort_unless($product->user_id === $this->user()?->id, 404);
        }

        return $this->user() !== null && ($product === null || $product->user_id === $this->user()->id);
    }

    public function rules(): array
    {
        return [
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'required', 'string', 'max:255'],
            'category_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'required', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'merchant' => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999.99'],
            'purchase_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'warranty_end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'note' => ['sometimes', 'nullable', 'string', 'max:300'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $product = $this->route('product');
            $purchase = $this->input('purchase_date', $product?->purchase_date?->format('Y-m-d'));
            $end = $this->input('warranty_end_date', $product?->warranty_end_date?->format('Y-m-d'));
            if (is_string($purchase) && is_string($end) && $end < $purchase) {
                $validator->errors()->add('warranty_end_date', 'Warranty end date must be on or after the purchase date.');
            }
        }];
    }
}
