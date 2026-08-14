<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Support;

use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;

final class ColorParser
{
    /**
     * @param  string|array<string, mixed>  $color
     * @return array{r: int, g: int, b: int, a: int}
     */
    public static function parse(string|array $color, int $defaultAlpha = 0): array
    {
        if (is_array($color)) {
            return [
                'r' => self::channel($color['r'] ?? 0, 'r'),
                'g' => self::channel($color['g'] ?? 0, 'g'),
                'b' => self::channel($color['b'] ?? 0, 'b'),
                'a' => self::alpha($color['a'] ?? $defaultAlpha),
            ];
        }

        $hex = ltrim(trim($color), '#');

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new InvalidQrCodeOptionException("Invalid hex color [{$color}]. Use #RRGGBB.");
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
            'a' => $defaultAlpha,
        ];
    }

    /**
     * @param  array{r: int, g: int, b: int, a?: int}  $color
     */
    public static function toHex(array $color): string
    {
        return sprintf('#%02x%02x%02x', $color['r'], $color['g'], $color['b']);
    }

    /**
     * Relative luminance (0–1) for WCAG-style contrast checks.
     *
     * @param  array{r: int, g: int, b: int, a?: int}  $color
     */
    public static function luminance(array $color): float
    {
        $channels = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }, [$color['r'], $color['g'], $color['b']]);

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }

    /**
     * @param  array{r: int, g: int, b: int, a?: int}  $foreground
     * @param  array{r: int, g: int, b: int, a?: int}  $background
     */
    public static function contrastRatio(array $foreground, array $background): float
    {
        $l1 = self::luminance($foreground);
        $l2 = self::luminance($background);
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private static function channel(mixed $value, string $name): int
    {
        $channel = (int) $value;

        if ($channel < 0 || $channel > 255) {
            throw new InvalidQrCodeOptionException("Color channel [{$name}] must be between 0 and 255.");
        }

        return $channel;
    }

    private static function alpha(mixed $value): int
    {
        $alpha = (int) $value;

        if ($alpha < 0 || $alpha > 127) {
            throw new InvalidQrCodeOptionException('Color alpha must be between 0 and 127.');
        }

        return $alpha;
    }
}
