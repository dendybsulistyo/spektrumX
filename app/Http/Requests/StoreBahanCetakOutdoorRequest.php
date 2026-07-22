<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBahanCetakOutdoorRequest extends FormRequest
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
        $bahanCetakOutdoor = $this->route('bahan_cetak_outdoor');

        return [
            'NmBhn' => ['required', 'string', 'max:30'],
            'NoUrut' => [
                'required', 'integer', 'min:0',
                Rule::unique('bahan_cetak_outdoor', 'NoUrut')->ignore($bahanCetakOutdoor?->NoUrut, 'NoUrut'),
            ],
            'NoCetak' => ['nullable', 'string', 'max:10'],
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
            'NmBhn' => 'nama bahan',
            'NoUrut' => 'nomor urut',
            'NoCetak' => 'nomor cetak',
        ];
    }
}
