<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'invoice_number' => ['required'],
            'date'           => ['nullable', 'date'],
            'total'          => ['required', 'numeric'],
            'status'         => ['nullable', 'integer'],
            'cashier_id'     => ['required', 'exists:users'],
            'customer_id'    => ['required', 'exists:customers'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
