<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PriceTableItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0.01', 'lt:price'],
            'wholesale_min_quantity' => ['nullable', 'integer', 'min:2', 'max:100000', 'required_with:wholesale_price'],
        ];
    }
}
