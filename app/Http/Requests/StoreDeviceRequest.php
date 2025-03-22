<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller via middleware
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
            'name' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:100'],
            'imei_1' => ['required', 'string', 'max:20', 'unique:devices,imei_1'],
            'imei_2' => ['nullable', 'string', 'max:20', 'unique:devices,imei_2'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_date' => ['nullable', 'date'],
        ];
    }
}
