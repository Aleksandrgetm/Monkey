<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');
        if ($document !== null && ! $this->is('api/admin/*')) {
            abort_unless($document->user_id === $this->user()?->id, 404);
        }

        return $this->user() !== null && ($document === null || $document->user_id === $this->user()->id || ($this->is('api/admin/*') && $this->user()->isAdmin()));
    }

    public function rules(): array
    {
        $ownerId = $this->route('document')?->user_id ?? $this->user()->id;

        return [
            'file' => ['bail', $this->route('document') ? 'sometimes' : 'required', 'file', 'mimetypes:application/pdf,image/jpeg,image/png', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value && ($value->getSize() >= 10485760 || $value->getSize() < 1)) {
                    $fail('The file must be greater than 0 bytes and strictly smaller than 10 MiB.');
                }
                if ($value && mb_strlen($value->getClientOriginalName()) > 255) {
                    $fail('The file name must not exceed 255 characters.');
                }
            }],
            'category_id' => [$this->route('document') ? 'sometimes' : 'required', 'required', 'integer', Rule::exists('categories', 'id')->where('user_id', $ownerId)],
            'product_id' => ['sometimes', 'nullable', 'integer', Rule::exists('products', 'id')->where('user_id', $ownerId)],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'kind' => ['sometimes', Rule::in(['receipt', 'warranty', 'other'])],
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
            $document = $this->route('document');
            $purchase = $this->input('purchase_date', $document?->purchase_date?->format('Y-m-d'));
            $end = $this->input('warranty_end_date', $document?->warranty_end_date?->format('Y-m-d'));
            if (is_string($purchase) && is_string($end) && $end < $purchase) {
                $validator->errors()->add('warranty_end_date', 'Warranty end date must be on or after the purchase date.');
            }
        }];
    }
}
