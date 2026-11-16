<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'sku' => ['required', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($product)],
            'ean' => ['nullable', 'string', 'max:20', Rule::unique('products', 'ean')->ignore($product)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'brand' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'active' => ['boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['active' => $this->boolean('active')]);
    }
}
