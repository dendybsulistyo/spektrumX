@props(['show' => false, 'days' => 3])

@if ($show)
    <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border: 1px solid #fecaca; border-radius: 50%; background: #fff1f2; font-size: 13px; line-height: 1;"
          title="Macet — sudah {{ $days }} hari atau lebih tidak berpindah tahap">
        ❗
    </span>
@endif
