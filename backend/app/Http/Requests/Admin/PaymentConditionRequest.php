<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('payment_conditions', 'code')->ignore($this->route('paymentCondition')),
            ],
            'description' => ['required', 'string', 'max:120'],
            'minimum_value' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'surcharge_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_term' => ['boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['is_term' => $this->boolean('is_term')]);
    }
}
