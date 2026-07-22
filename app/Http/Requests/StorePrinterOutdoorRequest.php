<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrinterOutdoorRequest extends FormRequest
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
        $printerOutdoor = $this->route('printer_outdoor');

        return [
            'KdPrn' => [
                'required', 'string', 'max:2',
                Rule::unique('printers_outdoors', 'KdPrn')->ignore($printerOutdoor?->id),
            ],
            'NmPrn' => ['required', 'string', 'max:20'],
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
            'KdPrn' => 'kode printer',
            'NmPrn' => 'nama printer',
            'NoUrut' => 'nomor urut',
        ];
    }
}
