<?php

namespace App\Http\Requests;

use App\Models\HargaArtwork;
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
            'replacement_order_id' => ['nullable', 'integer', 'exists:order_indoor,id'],

            'items' => ['required', 'array', 'min:1'],
            // KdProd's existence depends on jenis_produk (produk_indoor vs
            // harga_artwork) — can't express as a static exists: rule,
            // checked in withValidator() below instead.
            'items.*.KdProd' => ['required', 'string'],
            'items.*.jenis_produk' => ['required', 'in:indoor,artwork'],
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
     * Order Indoor now accepts items from two catalogs (produk_indoor for
     * "indoor" items, harga_artwork for "artwork" items — see
     * OrderIndoorDetail::jenis_produk), so KdProd existence and the
     * Jasa-Potong-required-fields check both need to look up the catalog
     * that matches each item's own jenis_produk rather than one fixed table.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            $indoorCodes = collect($items)->where('jenis_produk', 'indoor')->pluck('KdProd')->filter()->unique();
            $artworkCodes = collect($items)->where('jenis_produk', 'artwork')->pluck('KdProd')->filter()->unique();

            $produkMap = Produk::whereIn('KdProd', $indoorCodes)->get()->keyBy('KdProd');
            $artworkMap = HargaArtwork::whereIn('KdProd', $artworkCodes)->get()->keyBy('KdProd');

            foreach ($items as $index => $item) {
                $jenisProduk = $item['jenis_produk'] ?? 'indoor';
                $kdProd = $item['KdProd'] ?? null;

                $produk = $jenisProduk === 'artwork'
                    ? $artworkMap->get($kdProd)
                    : $produkMap->get($kdProd);

                if (! $produk) {
                    $validator->errors()->add("items.{$index}.KdProd", 'Produk tidak ditemukan.');

                    continue;
                }

                $isJasaPotong = $jenisProduk === 'artwork'
                    ? $produk->isJasaPotong()
                    : $produk->isPjLb === Produk::PJLB_QTY_ALT;

                if (! $isJasaPotong) {
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
