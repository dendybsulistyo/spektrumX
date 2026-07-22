<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKategoriProdukIndoorRequest extends FormRequest
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
        $kategoriProdukIndoor = $this->route('kategori_produk_indoor');

        return [
            'KdDivs' => [
                'required', 'string', 'max:2',
                Rule::unique('kategori_produk_indoor', 'KdDivs')->ignore($kategoriProdukIndoor?->id),
            ],
            'NmDivs' => ['required', 'string', 'max:19'],
            'NoUrut' => ['required', 'integer', 'min:0'],
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
            'KdDivs' => 'kode divisi',
            'NmDivs' => 'nama divisi',
            'NoUrut' => 'nomor urut',
        ];
    }
}
