<?php

namespace App\Http\Requests;

use App\Models\Produk;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderIndoorRequest extends FormRequest
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
        return [
            'TglOrder' => ['required', 'date'],
            'KdCust' => ['required', 'string', 'exists:customers,KdCust'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.KdProd' => ['required', 'string', 'exists:produk_indoor,KdProd'],
            'items.*.Judul' => ['required', 'string', 'max:30'],
            'items.*.Panjang' => ['required', 'numeric', 'min:0'],
            'items.*.Lebar' => ['required', 'numeric', 'min:0'],
            'items.*.Qty' => ['required', 'integer', 'min:1'],
            'items.*.PisauTurun' => ['nullable', 'integer', 'min:0'],
            'items.*.JumlahKertas' => ['nullable', 'integer', 'min:0'],
            'items.*.TebalKertas' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Produk with isPjLb === Produk::PJLB_QTY_ALT (4, "Jasa Potong") price
     * entirely from PisauTurun/JumlahKertas/TebalKertas — require all three
     * for those items specifically, since the plain "required" rule can't
     * look up the related produk's isPjLb.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);
            $kodeList = collect($items)->pluck('KdProd')->filter()->unique();
            $produkMap = Produk::whereIn('KdProd', $kodeList)->get()->keyBy('KdProd');

            foreach ($items as $index => $item) {
                $produk = $produkMap->get($item['KdProd'] ?? null);

                if (! $produk || $produk->isPjLb !== Produk::PJLB_QTY_ALT) {
                    continue;
                }

                foreach (['PisauTurun', 'JumlahKertas', 'TebalKertas'] as $field) {
                    if (empty($item[$field])) {
                        $validator->errors()->add(
                            "items.{$index}.{$field}",
                            'Wajib diisi untuk produk Jasa Potong.',
                        );
                    }
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'TglOrder' => 'tanggal order',
            'KdCust' => 'customer',
            'items.*.KdProd' => 'produk',
            'items.*.Judul' => 'judul',
            'items.*.Panjang' => 'panjang',
            'items.*.Lebar' => 'lebar',
            'items.*.Qty' => 'qty',
            'items.*.PisauTurun' => 'pisau turun',
            'items.*.JumlahKertas' => 'jumlah kertas',
            'items.*.TebalKertas' => 'tebal kertas',
        ];
    }
}
