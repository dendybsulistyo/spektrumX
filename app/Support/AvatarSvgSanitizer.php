<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Penyaring ketat SVG avatar. Hanya elemen & atribut yang dipakai gaya
 * Lorelei + lapisan hijab yang diizinkan; nilai atribut dicek polanya.
 * Apa pun di luar daftar → ditolak (null), bukan sekadar dibuang.
 */
class AvatarSvgSanitizer
{
    public const MAX_BYTES = 30000;

    private const ELEMENTS = ['svg', 'g', 'path', 'rect', 'mask'];

    private const NUMBER = '/^-?\d+(\.\d+)?$/';

    private const PAINT = '/^(none|#[0-9a-fA-F]{3,8}|url\(#viewboxMask\))$/';

    /** @return array<string, string> atribut → pola regex nilai */
    private static function attributes(): array
    {
        return [
            'xmlns' => '/^http:\/\/www\.w3\.org\/2000\/svg$/',
            'viewBox' => '/^0 0 980 980$/',
            'shape-rendering' => '/^auto$/',
            'id' => '/^viewboxMask$/',
            'mask' => '/^url\(#viewboxMask\)$/',
            'transform' => '/^translate\(-?\d+(\.\d+)? -?\d+(\.\d+)?\)$/',
            'd' => '/^[MmLlHhVvCcSsQqTtAaZz0-9.,\s-]+$/',
            'fill' => self::PAINT,
            'stroke' => self::PAINT,
            'fill-rule' => '/^(evenodd|nonzero)$/',
            'clip-rule' => '/^(evenodd|nonzero)$/',
            'stroke-width' => self::NUMBER,
            'stroke-linejoin' => '/^(round|miter|bevel)$/',
            'stroke-linecap' => '/^(round|butt|square)$/',
            'opacity' => '/^(0|1|0?\.\d+)$/',
            'width' => self::NUMBER,
            'height' => self::NUMBER,
            'x' => self::NUMBER,
            'y' => self::NUMBER,
            'rx' => self::NUMBER,
            'ry' => self::NUMBER,
            'class' => '/^[a-z-]+$/',
        ];
    }

    /** SVG bersih (string) atau null bila tidak lolos. */
    public static function sanitize(string $svg): ?string
    {
        $svg = trim($svg);
        if ($svg === '' || strlen($svg) > self::MAX_BYTES || ! str_starts_with($svg, '<svg')) {
            return null;
        }
        if (preg_match('/<!|<\?|&/', $svg)) {
            return null; // tanpa DOCTYPE, entity, komentar, atau processing instruction
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded || ! $dom->documentElement || $dom->documentElement->nodeName !== 'svg') {
            return null;
        }

        return self::isClean($dom->documentElement) ? $dom->saveXML($dom->documentElement) : null;
    }

    private static function isClean(DOMNode $node): bool
    {
        if ($node instanceof DOMElement) {
            if (! in_array($node->nodeName, self::ELEMENTS, true)) {
                return false;
            }
            $rules = self::attributes();
            foreach ($node->attributes as $attribute) {
                $rule = $rules[$attribute->nodeName] ?? null;
                if (! $rule || ! preg_match($rule, $attribute->nodeValue)) {
                    return false;
                }
            }
        } elseif ($node->nodeType === XML_TEXT_NODE) {
            return trim($node->nodeValue) === '';
        } else {
            return false;
        }

        foreach ($node->childNodes as $child) {
            if (! self::isClean($child)) {
                return false;
            }
        }

        return true;
    }
}
