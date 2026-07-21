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

            'items' => ['required', 'array', 'min:1'],
            'items.*.NmFile' => ['required', 'string', 'max:50'],
            'items.*.Panjang' => ['required', 'numeric', 'min:0'],
            'items.*.Lebar' => ['required', 'numeric', 'min:0'],
            'items.*.Qty' => ['required', 'integer', 'min:1'],
            'items.*.KdCtk' => ['nullable', 'string', 'exists:harga_cetak_outdoor,KdCtk'],
            'items.*.KdBrgs' => ['nullable', 'string', 'exists:bahan_outdoor,KdBrgs'],
            'items.*.Fins' => ['nullable', 'string', 'max:100'],
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
            'items.*.KdCtk' => 'kode cetak',
            'items.*.KdBrgs' => 'bahan',
            'items.*.Fins' => 'finishing',
        ];
    }
}
