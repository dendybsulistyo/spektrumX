<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Cetak Nota Pesanan / SO</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto bg-white border border-gray-200 rounded-lg p-6">
        @if (session('status'))
            <p class="mb-4 text-sm text-green-700" role="status">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('pengaturan.cetak-sales-order.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
                    <div id="cetak-sales-order" class="border-t pt-4" style="scroll-margin-top: 100px;">
                        <input type="hidden" name="auto_print_sales_order" value="0">
                        <label class="inline-flex items-center gap-2" for="auto_print_sales_order">
                            <input type="checkbox" name="auto_print_sales_order" value="1" id="auto_print_sales_order"
                                   @checked(old('auto_print_sales_order', $pengaturan->auto_print_sales_order))
                                   class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span class="text-sm font-medium text-gray-700">Cetak otomatis Nota Pesanan / Sales Order</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">Berlaku untuk semua kasir setelah pembayaran, DP, pencatatan hutang VIP, atau pelunasan. Nonaktifkan saat kertas cetakan habis. Cetak manual tetap tersedia.</p>
                        <x-input-error :messages="$errors->get('auto_print_sales_order')" class="mt-1" />
                    </div>

            <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">Simpan</button>
        </form>
    </div>
</x-app-layout>
