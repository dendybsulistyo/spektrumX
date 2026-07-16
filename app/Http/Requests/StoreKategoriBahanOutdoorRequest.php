<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKategoriBahanOutdoorRequest extends FormRequest
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
        $kategori = $this->route('kategori_bahan_outdoor');

        return [
            'KdGrup' => [
                'required', 'string', 'max:3',
                Rule::unique('aman_gd_grup_Bahan', 'KdGrup')->ignore($kategori?->id),
            ],
            'NmGrup' => ['required', 'string', 'max:50'],
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
            'KdGrup' => 'kode grup',
            'NmGrup' => 'nama grup',
            'NoUrut' => 'nomor urut',
        ];
    }
}
