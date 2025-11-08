<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'    => ['required'],
            'email'   => ['required', 'email', 'max:254'],
            'phone'   => ['nullable'],
            'address' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
