<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBahanOutdoorRequest extends FormRequest
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
        $bahan = $this->route('bahan_outdoor');

        return [
            'KdBrgs' => [
                'required', 'string', 'max:8',
                Rule::unique('bahan_outdoor', 'KdBrgs')->ignore($bahan?->id),
            ],
            'KdGrup' => ['nullable', 'string', 'exists:kategori_bahan_outdoor,KdGrup'],
            'NmBrgs' => ['required', 'string', 'max:50'],
            'Keters' => ['required', 'string', 'max:30'],
            'Satuan' => ['required', 'string', 'max:10'],
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
            'KdBrgs' => 'kode bahan',
            'NmBrgs' => 'nama bahan',
            'Keters' => 'keterangan',
            'Satuan' => 'satuan',
            'NoUrut' => 'nomor urut',
        ];
    }
}
