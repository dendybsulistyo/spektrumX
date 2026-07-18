<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHargaArtworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Unchecked checkboxes send nothing at all — normalize here since these
     * are NOT NULL columns with no default. isHPilih is a legacy code
     * column (1 = Ya, 2 = Tidak), not a 0/1 boolean like isPjLb.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isPjLb' => $this->boolean('isPjLb'),
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
        $hargaArtwork = $this->route('harga_artwork');

        return [
            'KdProd' => [
                'required', 'string', 'max:4',
                Rule::unique('harga_artwork', 'KdProd')->ignore($hargaArtwork?->id),
            ],
            'KdDivs' => ['nullable', 'string', 'exists:kategori_produk_indoor,KdDivs'],
            'NmProd' => ['required', 'string', 'max:30'],
            'NoUrut' => ['required', 'integer', 'min:0'],
            'HargaStd' => ['required', 'numeric', 'min:0'],
            'HargaMin' => ['required', 'numeric', 'min:0'],
            'Satuan' => ['required', 'string', 'max:8'],
            'isPjLb' => ['nullable', 'boolean'],
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
            'KdProd' => 'kode produk',
            'NmProd' => 'nama produk',
            'NoUrut' => 'nomor urut',
            'HargaStd' => 'harga standar',
            'HargaMin' => 'harga minimum',
            'Satuan' => 'satuan',
        ];
    }
}
