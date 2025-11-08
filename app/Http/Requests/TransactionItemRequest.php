<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'exists:transactions'],
            'product_id'     => ['required', 'exists:products'],
            'qtty'           => ['required', 'integer'],
            'unit_price'     => ['required', 'numeric'],
            'discount'       => ['required', 'numeric'],
            'subtotal'       => ['required', 'numeric'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
