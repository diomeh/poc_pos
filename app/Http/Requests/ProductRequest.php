<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sku'         => ['required'],
            'name'        => ['required'],
            'description' => ['nullable'],
            'price'       => ['nullable', 'numeric'],
            'cost'        => ['nullable', 'numeric'],
            'stock_qtty'  => ['nullable', 'integer'],
            'is_active'   => ['nullable', 'boolean'],
            'category_id' => ['required', 'exists:categories'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
