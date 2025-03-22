<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
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
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'device_ids' => ['required', 'array', 'min:1'],
            'device_ids.*' => ['exists:devices,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'qc_passed' => ['boolean'],
        ];
    }
}
