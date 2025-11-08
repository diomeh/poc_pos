<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'method'    => ['required'],
            'amount'    => ['required', 'numeric'],
            'reference' => ['required'],
            'status'    => ['required', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
