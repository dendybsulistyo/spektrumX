<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHargaCetakOutdoorRequest extends FormRequest
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
        $harga = $this->route('harga_cetak_outdoor');

        return [
            'KdCtk' => [
                'required', 'string', 'max:4',
                Rule::unique('harga_cetak_outdoor', 'KdCtk')->ignore($harga?->KdCtk, 'KdCtk'),
            ],
            'HargaStd' => ['required', 'numeric', 'min:0'],
            'HargaMin' => ['required', 'numeric', 'min:0'],
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
            'KdCtk' => 'kode cetak',
            'HargaStd' => 'harga standar',
            'HargaMin' => 'harga minimum',
        ];
    }
}
