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
     * isHPilih comes from a checkbox (unchecked sends nothing at all) and is
     * normalized to its legacy code: 1 = Ya, 2 = Tidak. isPjLb is a select
     * with 4 legacy codes (1-4, see Produk::PJLB_LABELS) and is sent as-is.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isHPilih' => $this->boolean('isHPilih') ? 1 : 2,
            // Form mengirim hidden 0 + checkbox 1; permintaan lama tanpa field ini = tetap diproses.
            'diproses' => $this->has('diproses') ? $this->boolean('diproses') : true,
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
                Rule::unique('produk_indoor', 'KdProd')->ignore($produk?->id),
            ],
            'KdDivs' => ['nullable', 'string', 'exists:kategori_produk_indoor,KdDivs'],
            'NmProd' => ['required', 'string', 'max:30'],
            'NoUrut' => ['required', 'integer', 'min:0'],
            'HargaStd' => ['required', 'numeric', 'min:0'],
            'HargaMin' => ['required', 'numeric', 'min:0'],
            'Satuan' => ['required', 'string', 'max:8'],
            'isPjLb' => ['required', 'integer', 'in:1,2,3,4'],
            'isHPilih' => ['required', 'integer', 'in:1,2'],
            'diproses' => ['required', 'boolean'],
            'bertingkat' => ['nullable', 'array'],
            'bertingkat.*.BatasA' => ['required', 'integer', 'min:0'],
            'bertingkat.*.BatasZ' => ['nullable', 'integer', 'min:0'],
            'bertingkat.*.Harga' => ['required', 'numeric', 'min:0'],
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
