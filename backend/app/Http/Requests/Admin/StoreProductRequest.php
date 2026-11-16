<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:40', 'unique:products,sku'],
            'ean' => ['nullable', 'string', 'max:20', 'unique:products,ean'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'brand' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'active' => ['boolean'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0.01', 'lt:price'],
            'wholesale_min_quantity' => ['nullable', 'integer', 'min:2', 'max:100000', 'required_with:wholesale_price'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['active' => $this->boolean('active')]);
    }
}
