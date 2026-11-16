<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cnpj' => ['required', 'string', 'max:20', 'unique:customers,cnpj'],
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
            'price_table_id' => ['required', 'integer', 'exists:price_tables,id'],
            'credit_limit' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'credit_used' => ['nullable', 'numeric', 'min:0', 'lte:credit_limit'],
            'blocked' => ['boolean'],
            'block_reason' => ['nullable', 'required_if:blocked,true', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6', 'max:72'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'blocked' => $this->boolean('blocked'),
            'state' => strtoupper((string) $this->input('state')),
        ]);
    }
}
