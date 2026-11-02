<?php

namespace App\Support;

/**
 * Saran gabung cetak outdoor (hanya saran, tidak menyimpan apa pun).
 *
 * Potongan (item × qty) dengan printer + bahan yang sama disusun ke lebar roll
 * memakai pola rak (First Fit Decreasing Height): potongan diurutkan dari yang
 * paling tinggi, diletakkan berjajar ke samping pada rak pertama yang masih
 * muat, atau membuka rak baru di bawahnya. Potongan boleh diputar 90°; sisi
 * panjang diletakkan melintang roll bila muat agar bahan yang terpakai pendek.
 * Semua ukuran dalam cm.
 */
class OutdoorNestingPlanner
{
    public const DEFAULT_MARGIN = 5;

    public const DEFAULT_GAP = 5;

    /** Batas potongan per kelompok agar halaman tetap ringan. */
    public const MAX_PIECES = 300;

    /**
     * Lebar roll (cm) dari nama bahan, mis. "FT China 280-3.2" → 320,
     * "White Gloss 80-1.52" → 152, "FL Coated 510gsm 3.2" → 320,
     * "Ri/Av WhiteGloss AP BO 1.55m" → 155. Null bila nama tidak memuat lebar.
     */
    public static function rollWidthFromName(?string $name): ?float
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        foreach (['/-\s*(\d+(?:\.\d+)?)\s*m?$/i', '/(\d+(?:\.\d+)?)\s*m$/i', '/\s(\d\.\d+)$/'] as $pattern) {
            if (preg_match($pattern, $name, $m)) {
                $meters = (float) $m[1];

                return $meters >= 0.5 && $meters <= 6 ? round($meters * 100, 1) : null;
            }
        }

        return null;
    }

    /**
     * @param  list<array{key:string, label:string, width:float, height:float, qty:int}>  $items
     * @return array{
     *     usable_width: float,
     *     length: float,
     *     efficiency: float,
     *     shelves: list<array{y: float, height: float, pieces: list<array{key:string, label:string, x:float, y:float, w:float, h:float, rotated:bool}>}>,
     *     too_wide: list<array{key:string, label:string, width:float, height:float}>,
     *     truncated: bool
     * }
     */
    public function plan(float $rollWidth, array $items, float $margin = self::DEFAULT_MARGIN, float $gap = self::DEFAULT_GAP): array
    {
        $usable = max(0, $rollWidth - 2 * $margin);
        $pieces = [];
        $tooWide = [];
        $truncated = false;

        foreach ($items as $item) {
            $long = max($item['width'], $item['height']);
            $short = min($item['width'], $item['height']);
            if ($short <= 0) {
                continue;
            }
            if ($short > $usable) {
                $tooWide[] = ['key' => $item['key'], 'label' => $item['label'], 'width' => $item['width'], 'height' => $item['height']];

                continue;
            }
            // Sisi panjang melintang roll bila muat (bahan terpakai lebih pendek); bila tidak, putar.
            [$w, $h] = $long <= $usable ? [$long, $short] : [$short, $long];
            for ($i = 0; $i < max(1, $item['qty']); $i++) {
                if (count($pieces) >= self::MAX_PIECES) {
                    $truncated = true;
                    break 2;
                }
                $pieces[] = ['key' => $item['key'], 'label' => $item['label'], 'w' => $w, 'h' => $h, 'rotated' => $w !== (float) $item['width']];
            }
        }

        usort($pieces, fn ($a, $b) => $b['h'] <=> $a['h'] ?: $b['w'] <=> $a['w']);

        $shelves = [];
        $y = $margin;
        foreach ($pieces as $piece) {
            $placed = false;
            foreach ($shelves as &$shelf) {
                $needed = $shelf['used'] > 0 ? $gap + $piece['w'] : $piece['w'];
                if ($piece['h'] <= $shelf['height'] && $shelf['used'] + $needed <= $usable) {
                    $x = $margin + $shelf['used'] + ($shelf['used'] > 0 ? $gap : 0);
                    $shelf['pieces'][] = $piece + ['x' => $x, 'y' => $shelf['y']];
                    $shelf['used'] += $needed;
                    $placed = true;
                    break;
                }
            }
            unset($shelf);
            if (! $placed) {
                if ($shelves) {
                    $y += $gap;
                }
                $shelves[] = ['y' => $y, 'height' => $piece['h'], 'used' => $piece['w'], 'pieces' => [$piece + ['x' => $margin, 'y' => $y]]];
                $y += $piece['h'];
            }
        }

        $length = $shelves ? $y + $margin : 0.0;
        $pieceArea = array_sum(array_map(fn ($p) => $p['w'] * $p['h'], $pieces));

        return [
            'usable_width' => $usable,
            'length' => $length,
            'efficiency' => $length > 0 && $rollWidth > 0 ? $pieceArea / ($rollWidth * $length) : 0.0,
            'shelves' => array_map(fn ($s) => [
                'y' => $s['y'],
                'height' => $s['height'],
                'pieces' => array_map(fn ($p) => [
                    'key' => $p['key'], 'label' => $p['label'], 'x' => $p['x'], 'y' => $p['y'],
                    'w' => $p['w'], 'h' => $p['h'], 'rotated' => $p['rotated'],
                ], $s['pieces']),
            ], $shelves),
            'too_wide' => $tooWide,
            'truncated' => $truncated,
        ];
    }
}
