<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800">Data Customer</h2>
            <a href="{{ route('customers.create') }}" class="btn btn-primary blueprint" style="height: 38px;">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>+ Tambah Customer
            </a>
        </div>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-customers { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-customers .tag-amber { background: #fef3c7; color: #92400e; }
        </style>
    @endpush

    <div id="industry-customers">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 280px;">
                        <label>Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau kode customer..." class="input">
                    </div>
                    <button type="submit" class="btn btn-primary" style="height: 36px;">Cari</button>
                    @if (request('search'))
                        <a href="{{ route('customers.index') }}" class="btn btn-ghost" style="height: 36px;">Reset</a>
                    @endif
                </form>
            </div>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 640px;">
                        <thead>
                            <tr>
                                <th style="width: 32px;">No</th><th>Kode</th><th>Nama customer</th><th>Alamat</th><th>Kota</th><th>Telepon</th><th>Tipe</th><th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $customer)
                                <tr>
                                    <td class="text-muted">{{ $customers->firstItem() + $loop->index }}</td>
                                    <td class="text-muted">{{ $customer->KdCust }}</td>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">{{ $customer->NmCust }}</td>
                                    <td class="text-muted">{{ $customer->Alamat }}</td>
                                    <td class="text-muted">{{ $customer->Kota }}</td>
                                    <td class="text-muted">{{ $customer->Telp }}</td>
                                    <td>
                                        @if ($customer->is_vip)
                                            <span class="tag tag-amber">VIP &middot; Rp {{ number_format($customer->limit->Batas, 0, ',', '.') }}</span>
                                        @else
                                            <span class="tag tag-neutral">Reguler</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 10px;">
                                            <a href="{{ route('customers.edit', $customer) }}" title="Edit" style="color: var(--color-accent); display: inline-flex;">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                            </a>
                                            <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                                                  onsubmit="return confirm('Hapus customer {{ $customer->NmCust }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus" style="color: #991b1b; display: inline-flex; background: none; border: none; cursor: pointer; padding: 0;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-muted" style="text-align: center; padding: var(--space-6);">Belum ada data customer.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: var(--space-4);">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
