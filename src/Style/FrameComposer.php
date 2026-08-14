<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Style;

use Tumutech\QrCode\Enums\Format;
use Tumutech\QrCode\Enums\FrameStyle;
use Tumutech\QrCode\Exceptions\QrCodeGenerationException;
use Tumutech\QrCode\Support\ColorParser;

final class FrameComposer
{
    /**
     * @param  array{r: int, g: int, b: int, a: int}  $background
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     */
    public function apply(
        string $contents,
        Format $format,
        FrameStyle $frame,
        string $label,
        int $qrSize,
        array $background,
        array $foreground,
    ): string {
        return match ($format) {
            Format::Png => $this->applyPng($contents, $frame, $label, $qrSize, $background, $foreground),
            Format::Svg => $this->applySvg($contents, $frame, $label, $qrSize, $background, $foreground),
            default => throw new QrCodeGenerationException('Frames are only supported for png and svg outputs.'),
        };
    }

    public function framedSize(int $qrSize, FrameStyle $frame): int
    {
        return match ($frame) {
            FrameStyle::None => $qrSize,
            FrameStyle::Badge => $qrSize + 72,
            FrameStyle::Card => $qrSize + 96,
        };
    }

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $background
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     */
    private function applyPng(
        string $contents,
        FrameStyle $frame,
        string $label,
        int $qrSize,
        array $background,
        array $foreground,
    ): string {
        $padding = $frame === FrameStyle::Card ? 24 : 16;
        $labelHeight = $frame === FrameStyle::Card ? 48 : 40;
        $canvasSize = $qrSize + ($padding * 2);
        $height = $canvasSize + $labelHeight;

        $qr = imagecreatefromstring($contents);
        if ($qr === false) {
            throw new QrCodeGenerationException('Unable to frame styled PNG.');
        }

        $canvas = imagecreatetruecolor($canvasSize, $height);
        if ($canvas === false) {
            throw new QrCodeGenerationException('Unable to create frame canvas.');
        }

        $bg = imagecolorallocate($canvas, $background['r'], $background['g'], $background['b']);
        $fg = imagecolorallocate($canvas, $foreground['r'], $foreground['g'], $foreground['b']);
        $accent = imagecolorallocate($canvas, max(0, $foreground['r'] - 20), max(0, $foreground['g'] - 20), max(0, $foreground['b'] - 20));
        imagefilledrectangle($canvas, 0, 0, $canvasSize, $height, $bg);
        imagerectangle($canvas, 0, 0, $canvasSize - 1, $height - 1, $accent);
        imagecopy($canvas, $qr, $padding, $padding, 0, 0, $qrSize, $qrSize);

        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($label);
        $textX = (int) max(0, ($canvasSize - $textWidth) / 2);
        $textY = $canvasSize + (int) max(2, ($labelHeight - imagefontheight($font)) / 2);
        imagestring($canvas, $font, $textX, $textY, strtoupper($label), $fg);

        ob_start();
        imagepng($canvas);
        imagedestroy($canvas);
        imagedestroy($qr);
        $out = ob_get_clean();

        if ($out === false) {
            throw new QrCodeGenerationException('Unable to encode framed PNG.');
        }

        return $out;
    }

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $background
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     */
    private function applySvg(
        string $contents,
        FrameStyle $frame,
        string $label,
        int $qrSize,
        array $background,
        array $foreground,
    ): string {
        $padding = $frame === FrameStyle::Card ? 24 : 16;
        $labelHeight = $frame === FrameStyle::Card ? 48 : 40;
        $width = $qrSize + ($padding * 2);
        $height = $width + $labelHeight;
        $bg = ColorParser::toHex($background);
        $fg = ColorParser::toHex($foreground);

        $inner = preg_replace('/<\?xml.*?\?>/', '', $contents) ?? $contents;
        $inner = preg_replace('/<svg[^>]*>/', '<g transform="translate('.$padding.' '.$padding.')">', $inner, 1) ?? $inner;
        $inner = preg_replace('/<\/svg>\s*$/', '</g>', $inner) ?? $inner;

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d">'
            .'<rect width="100%%" height="100%%" fill="%s" stroke="%s" stroke-width="2" rx="%d"/>'
            .'%s'
            .'<text x="50%%" y="%d" text-anchor="middle" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="18" font-weight="700" fill="%s">%s</text>'
            .'</svg>',
            $width,
            $height,
            $width,
            $height,
            $bg,
            $fg,
            $frame === FrameStyle::Card ? 18 : 12,
            $inner,
            $width + (int) round($labelHeight * 0.65),
            $fg,
            htmlspecialchars(strtoupper($label), ENT_QUOTES),
        );
    }
}
