<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderOutdoorRequest extends FormRequest
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
            'replacement_order_id' => ['nullable', 'integer', 'exists:order_outdoor,id'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.NmFile' => ['required', 'string', 'max:50'],
            'items.*.Panjang' => ['required', 'numeric', 'min:0'],
            'items.*.Lebar' => ['required', 'numeric', 'min:0'],
            'items.*.Qty' => ['required', 'integer', 'min:1'],
            'items.*.KdCtk' => ['nullable', 'string', 'exists:harga_cetak_outdoor,KdCtk'],
            'items.*.ada_finishing' => ['nullable', 'in:ya,tidak'],
            'items.*.jenis_finishing' => ['nullable', 'string', 'max:50'],
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
            'items.*.NmFile' => 'nama file',
            'items.*.Panjang' => 'panjang',
            'items.*.Lebar' => 'lebar',
            'items.*.Qty' => 'qty',
            'items.*.KdCtk' => 'printer & bahan cetak',
            'items.*.ada_finishing' => 'ada finishing',
            'items.*.jenis_finishing' => 'jenis finishing',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.KdCtk.exists' => 'Kombinasi printer & bahan cetak yang dipilih belum punya harga — atur dulu di Standar Harga, atau pilih kombinasi lain.',
        ];
    }
}
