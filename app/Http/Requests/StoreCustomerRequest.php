<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'KdCust' => [
                'required', 'string', 'max:6',
                Rule::unique('customers', 'KdCust')->ignore($customer?->id),
            ],
            'NmCust' => ['required', 'string', 'max:50'],
            'Alamat' => ['required', 'string', 'max:60'],
            'Kota' => ['required', 'string', 'max:25'],
            'Telp' => ['required', 'string', 'max:40'],
            'is_vip' => ['nullable', 'boolean'],
            'Batas' => ['required_if:is_vip,1', 'nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'KdCust' => 'kode customer',
            'NmCust' => 'nama customer',
            'Alamat' => 'alamat',
            'Kota' => 'kota',
            'Telp' => 'telepon',
            'Batas' => 'limit piutang',
        ];
    }
}
