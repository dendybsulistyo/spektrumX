<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AvatarSvgSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Avatar mini: hanya pemilik akun yang bisa mengubah avatarnya sendiri. */
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
        $request->user()->forceFill(['avatar_options' => null, 'avatar_svg' => null, 'avatar_updated_at' => null])->save();

        return response()->json(['ok' => true]);
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
