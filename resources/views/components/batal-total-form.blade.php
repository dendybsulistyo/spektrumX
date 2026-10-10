{{--
    Tombol "Setujui Batal Total". Kalau order sudah ada uang masuk / piutang,
    tombol membuka panel kecil untuk nominal refund (Tunai/Transfer).
    Logika keuangan: App\Services\OrderCancellationRefund.
--}}
@props(['order', 'type', 'buttonClass' => 'in-btn in-btn-danger', 'label' => 'Setujui Batal Total'])
@php
    $paid = \App\Services\OrderCancellationRefund::refundable($order);
    $receivable = \App\Services\OrderCancellationRefund::openReceivable($order);
    $action = route('order-'.$type.'.approve-cancel', $order->id);
    $key = $type.'-'.$order->id;
    $isOld = old('cancel_key') === $key;
    $rp = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

@if ($paid <= 0 && $receivable <= 0)
    <form method="POST" action="{{ $action }}" style="display:inline"
          onsubmit="return confirm('Setujui pembatalan TOTAL order {{ $order->NoOrder }}? Tidak akan ada nota pengganti.')">
        @csrf
        <input type="hidden" name="resolution" value="batal_total">
        <button type="submit" class="{{ $buttonClass }}">{{ $label }}</button>
    </form>
@else
    <div x-data="{ open: @js($isOld && $errors->hasAny(['refund_amount', 'refund_method', 'refund_reference'])), method: @js($isOld ? old('refund_method', 'tunai') : 'tunai') }"
         style="position:relative; display:inline-block; text-align:left">
        <button type="button" class="{{ $buttonClass }}" @click="open = !open">{{ $label }}</button>

        <template x-teleport="body">
        <div x-show="open" x-cloak class="bt-backdrop" @keydown.escape.window="open = false" @click.self="open = false">
        <form method="POST" action="{{ $action }}" class="bt-panel"
              onsubmit="return confirm('Setujui pembatalan TOTAL order {{ $order->NoOrder }}?')">
            @csrf
            <input type="hidden" name="resolution" value="batal_total">
            <input type="hidden" name="cancel_key" value="{{ $key }}">

            <div class="bt-title">Batal Total · {{ $order->NoOrder }}</div>

            @if ($receivable > 0)
                <div class="bt-info">Sisa piutang <b>Rp {{ $rp($receivable) }}</b> dihapus dan plafon customer dikembalikan.</div>
            @endif

            @if ($paid > 0)
                <div class="bt-info">Sudah dibayar customer: <b>Rp {{ $rp($paid) }}</b></div>

                <label class="bt-label">Uang dikembalikan (Rp)
                    <input type="text" inputmode="numeric" name="refund_amount" class="bt-input"
                           value="{{ $isOld ? old('refund_amount') : $rp($paid) }}"
                           x-on:input="$el.value = $el.value.replace(/[^\d]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                </label>
                <div class="bt-hint">Boleh dikurangi; sisa yang tidak dikembalikan tetap jadi pendapatan. Isi 0 bila tidak ada pengembalian.</div>

                <div class="bt-label">Cara pengembalian</div>
                <div class="bt-seg">
                    @foreach (\App\Services\OrderCancellationRefund::METHODS as $value => $text)
                        <label :class="method === '{{ $value }}' && 'is-active'">
                            <input type="radio" name="refund_method" value="{{ $value }}" x-model="method"> {{ $text }}
                        </label>
                    @endforeach
                </div>

                <label class="bt-label" x-show="method === 'transfer'">No. referensi transfer
                    <input type="text" name="refund_reference" maxlength="100" class="bt-input" value="{{ $isOld ? old('refund_reference') : '' }}">
                </label>
            @endif

            @if ($isOld)
                @foreach (['refund_amount', 'refund_method', 'refund_reference'] as $field)
                    @error($field)<div class="bt-error">{{ $message }}</div>@enderror
                @endforeach
            @endif

            <div class="bt-actions">
                <button type="button" class="bt-btn bt-btn-ghost" @click="open = false">Tutup</button>
                <button type="submit" class="bt-btn bt-btn-danger">Setujui Batal Total</button>
            </div>
        </form>
        </div>
        </template>
    </div>

    @once
        <style>
            .bt-backdrop { position: fixed; inset: 0; z-index: 70; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(27, 34, 54, .35); }
            .bt-panel { width: 100%; max-width: 340px; text-align: left; padding: 14px; background: #fffdf8; border: 1px solid #cfc7b5; border-radius: 10px; box-shadow: 0 18px 40px -18px rgba(27, 34, 54, .45); font-size: 13px; color: #262a33; white-space: normal; }
            .bt-title { margin-bottom: 8px; font-weight: 700; color: #1b2236; }
            .bt-info { margin-bottom: 8px; padding: 6px 8px; background: #f1ece0; border-radius: 6px; line-height: 1.4; }
            .bt-label { display: block; margin-top: 8px; font-size: 12px; font-weight: 600; color: #5d5a52; }
            .bt-input { display: block; width: 100%; height: 34px; margin-top: 4px; padding: 0 8px; font-size: 14px; border: 1px solid #cfc7b5; border-radius: 6px; text-align: right; }
            .bt-hint { margin-top: 4px; font-size: 11.5px; line-height: 1.35; color: #77736a; }
            .bt-seg { display: flex; gap: 6px; margin-top: 4px; }
            .bt-seg label { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; height: 32px; font-weight: 600; border: 1px solid #cfc7b5; border-radius: 6px; cursor: pointer; background: #fff; }
            .bt-seg label.is-active { color: #fff; background: #1b2236; border-color: #1b2236; }
            .bt-seg input { display: none; }
            .bt-error { margin-top: 8px; font-size: 12px; color: #c8246c; }
            .bt-actions { display: flex; justify-content: flex-end; gap: 6px; margin-top: 12px; }
            .bt-btn { height: 32px; padding: 0 12px; font-size: 13px; font-weight: 600; border-radius: 6px; cursor: pointer; }
            .bt-btn-ghost { color: #1b2236; background: #fff; border: 1px solid #cfc7b5; }
            .bt-btn-danger { color: #fff; background: #b91c1c; border: 1px solid #b91c1c; }
        </style>
    @endonce
@endif
