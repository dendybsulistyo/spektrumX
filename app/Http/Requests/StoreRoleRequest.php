<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
        $role = $this->route('role');

        return [
            'name' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],
            'label' => ['required', 'string', 'max:50'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(Role::allPermissionKeys()))],
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
            'name' => 'kode role',
            'label' => 'nama role',
        ];
    }
}
