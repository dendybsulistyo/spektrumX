<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderIndoorRequest extends FormRequest
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
        return [
            'TglOrder' => ['required', 'date'],
            'KdCust' => ['required', 'string', 'exists:customers,KdCust'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.KdProd' => ['required', 'string', 'exists:produk_indoor,KdProd'],
            'items.*.Judul' => ['required', 'string', 'max:30'],
            'items.*.Panjang' => ['required', 'numeric', 'min:0'],
            'items.*.Lebar' => ['required', 'numeric', 'min:0'],
            'items.*.Qty' => ['required', 'integer', 'min:1'],
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
            'TglOrder' => 'tanggal order',
            'KdCust' => 'customer',
            'items.*.KdProd' => 'produk',
            'items.*.Judul' => 'judul',
            'items.*.Panjang' => 'panjang',
            'items.*.Lebar' => 'lebar',
            'items.*.Qty' => 'qty',
        ];
    }
}
