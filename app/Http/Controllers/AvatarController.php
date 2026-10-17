<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AvatarSvgSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Avatar mini & foto pribadi: hanya pemilik akun yang bisa mengubah miliknya sendiri. */
class AvatarController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $hair = array_merge(
            ['variant01', 'variant02', 'variant03', 'variant04', 'variant06', 'variant08', 'variant09', 'variant28', 'variant39', 'variant47'],
            ['variant13', 'variant15', 'variant16', 'variant19', 'variant21', 'variant23', 'variant24', 'variant32', 'variant35', 'variant40'],
        );
        $data = $request->validate([
            'options' => ['required', 'array'],
            'options.gender' => ['required', Rule::in(['pria', 'wanita'])],
            'options.hijab' => ['required', 'boolean'],
            'options.hair' => ['required', Rule::in($hair)],
            'options.eyes' => ['required', 'regex:/^variant(0[1-9]|1[0-2])$/'],
            'options.eyebrows' => ['required', 'regex:/^variant0[1-6]$/'],
            'options.mouth' => ['required', 'regex:/^happy(0[1-9]|10)$/'],
            'options.nose' => ['required', 'regex:/^variant0[1-6]$/'],
            'options.glasses' => ['required', 'boolean'],
            'options.beard' => ['required', 'boolean'],
            'options.hijabColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'options.background' => ['required', 'regex:/^[0-9a-fA-F]{6}$/'],
            'options.hat' => ['nullable', Rule::in(['none', 'pet', 'kupluk', 'peci'])],
            'options.hatColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'svg' => ['required', 'string', 'max:'.AvatarSvgSanitizer::MAX_BYTES],
        ]);

        $svg = AvatarSvgSanitizer::sanitize($data['svg']);
        abort_if($svg === null, 422, 'Gambar avatar tidak valid.');

        $request->user()->forceFill([
            'avatar_options' => $data['options'],
            'avatar_svg' => $svg,
            'avatar_updated_at' => now(),
        ])->save();

        return response()->json(['url' => $request->user()->fresh()->avatarUrl()]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill([
            'avatar_options' => null,
            'avatar_svg' => null,
            // Waktu ubah tetap dipakai sebagai versi URL foto pribadi bila masih ada.
            'avatar_updated_at' => $user->avatar_photo ? now() : null,
        ])->save();

        return response()->json(['ok' => true, 'url' => $user->fresh()->avatarUrl()]);
    }

    /** Batas ukuran foto pribadi (KB). */
    public const PHOTO_MAX_KB = 1024;

    /**
     * Foto pribadi: browser sudah memotongnya persegi & mengecilkannya, tapi
     * server tetap memeriksa jenis, ukuran file (maks. 1 MB) dan isi gambar.
     */
    public function storePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.self::PHOTO_MAX_KB],
        ], [
            'photo.max' => 'Ukuran foto maksimal 1 MB.',
            'photo.mimes' => 'Foto harus berupa JPG, PNG atau WEBP.',
        ]);

        $file = $request->file('photo');
        $info = @getimagesize($file->getRealPath());
        abort_unless($info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            && $info[0] >= 32 && $info[1] >= 32 && $info[0] <= 4000 && $info[1] <= 4000, 422, 'File bukan foto yang valid.');

        $user = $request->user();
        $extension = image_type_to_extension($info[2], false) === 'jpeg' ? 'jpg' : image_type_to_extension($info[2], false);
        $path = 'avatars/'.$user->id.'-'.Str::random(12).'.'.$extension;
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        $old = $user->avatar_photo;
        $user->forceFill(['avatar_photo' => $path, 'avatar_updated_at' => now()])->save();
        if ($old && $old !== $path) {
            Storage::disk('local')->delete($old);
        }

        return response()->json(['url' => $user->fresh()->avatarUrl()]);
    }

    public function destroyPhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->avatar_photo) {
            Storage::disk('local')->delete($user->avatar_photo);
        }
        $user->forceFill([
            'avatar_photo' => null,
            'avatar_updated_at' => $user->avatar_svg ? now() : null,
        ])->save();

        return response()->json(['ok' => true, 'url' => $user->fresh()->avatarUrl()]);
    }

    /** Foto pribadi disajikan hanya untuk user yang login, sebagai gambar (bukan unduhan). */
    public function photo(User $user): StreamedResponse
    {
        abort_unless($user->avatar_photo && Storage::disk('local')->exists($user->avatar_photo), 404);

        return Storage::disk('local')->response($user->avatar_photo, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    /** Disajikan sebagai gambar; CSP melarang skrip apa pun di dalam SVG. */
    public function show(User $user): Response
    {
        abort_unless($user->avatar_svg, 404);

        return response($user->avatar_svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }
}
