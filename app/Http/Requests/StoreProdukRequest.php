<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProdukRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Unchecked checkboxes send nothing at all — normalize to 0/1 here since
     * isPjLb/isHPilih are NOT NULL columns with no default.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isPjLb' => $this->boolean('isPjLb'),
            'isHPilih' => $this->boolean('isHPilih'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $produk = $this->route('produk');

        return [
            'KdProd' => [
                'required', 'string', 'max:4',
                Rule::unique('z_produk_NamaProduk_INDOOR_CEK', 'KdProd')->ignore($produk?->id),
            ],
            'KdDivs' => ['nullable', 'string', 'exists:aman_divisi_Master_Produk_Indoor,KdDivs'],
            'NmProd' => ['required', 'string', 'max:30'],
            'NoUrut' => ['required', 'integer', 'min:0'],
            'HargaStd' => ['required', 'numeric', 'min:0'],
            'HargaMin' => ['required', 'numeric', 'min:0'],
            'Satuan' => ['required', 'string', 'max:8'],
            'isPjLb' => ['nullable', 'boolean'],
            'isHPilih' => ['nullable', 'boolean'],
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
            'KdProd' => 'kode produk',
            'NmProd' => 'nama produk',
            'NoUrut' => 'nomor urut',
            'HargaStd' => 'harga standar',
            'HargaMin' => 'harga minimum',
            'Satuan' => 'satuan',
        ];
    }
}
