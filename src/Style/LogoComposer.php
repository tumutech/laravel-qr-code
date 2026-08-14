<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Style;

use Tumutech\QrCode\Exceptions\QrCodeGenerationException;

/**
 * Centers a brand mark on a QR code, optionally clearing
 * modules behind it with a padded background "punchout".
 */
final class LogoComposer
{
    public const DEFAULT_RATIO = 0.22;

    public const PAD_RATIO = 0.18;

    public function intendedWidth(int $qrSize, ?int $logoWidth): int
    {
        return max(1, $logoWidth ?? (int) round($qrSize * self::DEFAULT_RATIO));
    }

    public function resolveWidth(int $qrSize, ?int $logoWidth): int
    {
        return max(8, min($this->intendedWidth($qrSize, $logoWidth), (int) round($qrSize * 0.4)));
    }

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $background
     */
    public function applyPng(
        string $png,
        string $logoPath,
        int $qrSize,
        ?int $logoWidth,
        array $background,
        bool $punchout = true,
    ): string {
        $qr = imagecreatefromstring($png);
        $logo = @imagecreatefromstring((string) file_get_contents($logoPath));

        if ($qr === false || $logo === false) {
            throw new QrCodeGenerationException('Unable to composite logo onto QR code.');
        }

        imagesavealpha($qr, true);
        imagealphablending($qr, true);

        $qrWidth = imagesx($qr);
        $qrHeight = imagesy($qr);
        $destW = $this->resolveWidth($qrSize, $logoWidth);
        $srcW = imagesx($logo);
        $srcH = imagesy($logo);
        $destH = (int) round($destW * ($srcH / max(1, $srcW)));

        $pad = (int) round($destW * self::PAD_RATIO);
        $boxW = $destW + ($pad * 2);
        $boxH = $destH + ($pad * 2);
        $boxX = (int) round(($qrWidth - $boxW) / 2);
        $boxY = (int) round(($qrHeight - $boxH) / 2);
        $destX = $boxX + $pad;
        $destY = $boxY + $pad;

        if ($punchout) {
            $this->fillRoundedRect(
                $qr,
                $boxX,
                $boxY,
                $boxW,
                $boxH,
                (int) round(min($boxW, $boxH) * 0.22),
                $background,
            );
        }

        imagecopyresampled($qr, $logo, $destX, $destY, 0, 0, $destW, $destH, $srcW, $srcH);

        ob_start();
        imagepng($qr);
        imagedestroy($qr);
        imagedestroy($logo);
        $out = ob_get_clean();

        if ($out === false) {
            throw new QrCodeGenerationException('Unable to encode logo-composited PNG.');
        }

        return $out;
    }

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $background
     */
    public function applySvg(
        string $svg,
        string $logoPath,
        int $size,
        ?int $logoWidth,
        array $background,
        bool $punchout = true,
    ): string {
        $bytes = file_get_contents($logoPath);
        if ($bytes === false) {
            throw new QrCodeGenerationException("Unable to read logo [{$logoPath}].");
        }

        $destW = $this->resolveWidth($size, $logoWidth);
        $pad = (int) round($destW * self::PAD_RATIO);
        $box = $destW + ($pad * 2);
        $boxX = ($size - $box) / 2;
        $logoX = $boxX + $pad;
        $radius = min($box, $box) * 0.22;
        $mime = mime_content_type($logoPath) ?: 'image/png';
        $dataUri = 'data:'.$mime.';base64,'.base64_encode($bytes);

        $parts = [];
        if ($punchout) {
            $parts[] = sprintf(
                '<rect x="%.2f" y="%.2f" width="%d" height="%d" rx="%.2f" ry="%.2f" fill="rgb(%d,%d,%d)" />',
                $boxX,
                $boxX,
                $box,
                $box,
                $radius,
                $radius,
                $background['r'],
                $background['g'],
                $background['b'],
            );
        }

        $parts[] = sprintf(
            '<image href="%s" x="%.2f" y="%.2f" width="%d" height="%d" preserveAspectRatio="xMidYMid meet" />',
            htmlspecialchars($dataUri, ENT_QUOTES),
            $logoX,
            $logoX,
            $destW,
            $destW,
        );

        $markup = implode('', $parts);

        return preg_replace('/<\/svg>\s*$/', $markup.'</svg>', $svg) ?? ($svg.$markup);
    }

    /**
     * @param  \GdImage  $image
     * @param  array{r: int, g: int, b: int, a: int}  $color
     */
    private function fillRoundedRect(
        $image,
        int $x,
        int $y,
        int $width,
        int $height,
        int $radius,
        array $color,
    ): void {
        $radius = max(0, min($radius, (int) floor(min($width, $height) / 2)));
        $fill = imagecolorallocatealpha(
            $image,
            $color['r'],
            $color['g'],
            $color['b'],
            max(0, min(127, $color['a'])),
        );

        if ($fill === false) {
            throw new QrCodeGenerationException('Unable to allocate punchout color for logo.');
        }

        imagefilledrectangle($image, $x + $radius, $y, $x + $width - $radius, $y + $height, $fill);
        imagefilledrectangle($image, $x, $y + $radius, $x + $width, $y + $height - $radius, $fill);
        imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $fill);
        imagefilledellipse($image, $x + $width - $radius, $y + $radius, $radius * 2, $radius * 2, $fill);
        imagefilledellipse($image, $x + $radius, $y + $height - $radius, $radius * 2, $radius * 2, $fill);
        imagefilledellipse($image, $x + $width - $radius, $y + $height - $radius, $radius * 2, $radius * 2, $fill);
    }
}
