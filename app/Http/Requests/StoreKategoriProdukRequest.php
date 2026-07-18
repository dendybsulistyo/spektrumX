<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKategoriProdukRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * isHPilih comes from a checkbox (unchecked sends nothing at all) and is
     * normalized to its legacy code: 1 = Ya, 2 = Tidak.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isHPilih' => $this->boolean('isHPilih') ? 1 : 2,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kategori_mode' => ['required', Rule::in(['existing', 'new'])],
            'KdDivs' => ['required_if:kategori_mode,existing', 'nullable', 'string', 'exists:kategori_produk_indoor,KdDivs'],
            'new_KdDivs' => [
                'required_if:kategori_mode,new', 'nullable', 'string', 'max:2',
                Rule::unique('kategori_produk_indoor', 'KdDivs'),
            ],
            'new_NmDivs' => ['required_if:kategori_mode,new', 'nullable', 'string', 'max:19'],
            'KategoriNoUrut' => ['required_if:kategori_mode,new', 'nullable', 'integer', 'min:0'],

            'KdProd' => ['required', 'string', 'max:4', Rule::unique('produk_indoor', 'KdProd')],
            'NmProd' => ['required', 'string', 'max:30'],
            'NoUrut' => ['required', 'integer', 'min:0'],
            'HargaStd' => ['required', 'numeric', 'min:0'],
            'HargaMin' => ['required', 'numeric', 'min:0'],
            'Satuan' => ['required', 'string', 'max:8'],
            'isPjLb' => ['required', 'integer', 'in:1,2,3,4'],
            'isHPilih' => ['required', 'integer', 'in:1,2'],
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
            'KdDivs' => 'kategori',
            'new_KdDivs' => 'kode kategori baru',
            'new_NmDivs' => 'nama kategori baru',
            'KategoriNoUrut' => 'nomor urut kategori',
            'KdProd' => 'kode produk',
            'NmProd' => 'nama produk',
            'NoUrut' => 'nomor urut produk',
            'HargaStd' => 'harga standar',
            'HargaMin' => 'harga minimum',
            'Satuan' => 'satuan',
        ];
    }
}
