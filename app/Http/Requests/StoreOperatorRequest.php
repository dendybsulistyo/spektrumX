<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperatorRequest extends FormRequest
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
        $operator = $this->route('operator');

        return [
            'KdOpr' => [
                'required', 'string', 'max:4',
                Rule::unique('operators', 'KdOpr')->ignore($operator?->KdOpr, 'KdOpr'),
            ],
            'NmOpr' => ['required', 'string', 'max:50'],
            'Status' => ['nullable', 'boolean'],
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
            'KdOpr' => 'kode operator',
            'NmOpr' => 'nama operator',
            'Status' => 'status aktif',
        ];
    }
}
