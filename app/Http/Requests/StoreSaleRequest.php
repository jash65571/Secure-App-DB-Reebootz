<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'store_id' => ['required', 'exists:stores,id'],
            'device_id' => ['required', 'exists:devices,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'on_emi' => ['sometimes'],
        ];

        // Add validation rules for EMI fields if on_emi is checked
        if ($this->has('on_emi')) {
            $rules['total_installments'] = ['required', 'integer', 'min:1'];
            $rules['emi_amount'] = ['required', 'numeric', 'min:0.01'];
            $rules['next_emi_date'] = ['required', 'date', 'after:today'];
        }

        return $rules;
    }
}
