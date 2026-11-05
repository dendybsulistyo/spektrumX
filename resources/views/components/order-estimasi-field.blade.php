@props(['value' => null, 'csDays' => null])

{{-- Estimasi pengerjaan: angka dari Lembar Kerja CS dikunci; kosong = 1 hari. --}}
<div style="width: 100%; max-width: 160px;">
    <x-input-label for="estimasi_hari" value="Estimasi" />
    @if ($csDays)
        <select id="estimasi_hari" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm" disabled
                title="Mengikuti Progress Hari dari Lembar Kerja CS">
            <option>{{ $csDays }} hari (CS)</option>
        </select>
    @else
        <select id="estimasi_hari" name="estimasi_hari" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">1 hari (default)</option>
            @foreach (range(1, 10) as $day)
                <option value="{{ $day }}" @selected((string) old('estimasi_hari', $value) === (string) $day)>{{ $day }} hari</option>
            @endforeach
        </select>
    @endif
    <x-input-error :messages="$errors->get('estimasi_hari')" class="mt-1" />
</div>
