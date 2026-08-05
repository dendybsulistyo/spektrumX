<?php

namespace App\Http\Requests;

use App\Models\HargaArtwork;
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
     * isPjLb is now a proper code select (1/2/4, same convention as
     * Produk::PJLB_LABELS) rather than a checkbox. isHPilih is still a
     * legacy checkbox-backed code column (1 = Ya, 2 = Tidak) — unchecked
     * checkboxes send nothing at all, so it's normalized here since it's a
     * NOT NULL column with no default.
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
            'isPjLb' => ['required', Rule::in(array_keys(HargaArtwork::PJLB_LABELS))],
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
