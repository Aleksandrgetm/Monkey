<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');
        if ($category !== null) {
            abort_unless($category->user_id === $this->user()?->id, 404);
        }

        return $this->user() !== null && ($category === null || $category->user_id === $this->user()->id);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100', Rule::unique('categories')->where('user_id', $this->user()->id)->ignore($this->route('category')?->id)]];
    }
}
