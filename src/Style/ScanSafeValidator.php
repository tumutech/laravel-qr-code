<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Style;

use Tumutech\QrCode\Enums\ErrorCorrection;
use Tumutech\QrCode\Enums\EyeStyle;
use Tumutech\QrCode\Enums\FrameStyle;
use Tumutech\QrCode\Enums\ModuleShape;
use Tumutech\QrCode\Exceptions\ScanUnsafeException;
use Tumutech\QrCode\Support\ColorParser;

final class ScanSafeValidator
{
    public const MIN_CONTRAST_RATIO = 4.5;

    public const MAX_LOGO_COVERAGE = 0.28;

    public const MIN_MODULE_MARGIN = 4;

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     * @param  array{r: int, g: int, b: int, a: int}  $background
     * @param  array{r: int, g: int, b: int, a: int}|null  $gradientEnd
     * @return list<string>
     */
    public function issues(
        array $foreground,
        array $background,
        ?array $gradientEnd,
        ModuleShape $module,
        EyeStyle $eye,
        ErrorCorrection $errorCorrection,
        int $size,
        int $moduleMargin,
        ?string $logoPath,
        ?int $logoWidth,
        FrameStyle $frame,
    ): array {
        $issues = [];

        $worstForeground = $foreground;
        if ($gradientEnd !== null) {
            $startContrast = ColorParser::contrastRatio($foreground, $background);
            $endContrast = ColorParser::contrastRatio($gradientEnd, $background);
            if (min($startContrast, $endContrast) < self::MIN_CONTRAST_RATIO) {
                $issues[] = sprintf(
                    'Gradient contrast is too low (min %.2f:1, need %.1f:1).',
                    min($startContrast, $endContrast),
                    self::MIN_CONTRAST_RATIO,
                );
            }
            $worstForeground = $startContrast <= $endContrast ? $foreground : $gradientEnd;
        } elseif (ColorParser::contrastRatio($foreground, $background) < self::MIN_CONTRAST_RATIO) {
            $issues[] = sprintf(
                'Foreground/background contrast is too low (%.2f:1, need %.1f:1).',
                ColorParser::contrastRatio($worstForeground, $background),
                self::MIN_CONTRAST_RATIO,
            );
        }

        if ($moduleMargin < self::MIN_MODULE_MARGIN) {
            $issues[] = 'Quiet zone (margin) should be at least 4 modules for reliable scanning.';
        }

        if ($logoPath !== null) {
            if ($errorCorrection !== ErrorCorrection::High) {
                $issues[] = 'Logos require high error correction.';
            }

            $width = (new LogoComposer)->intendedWidth($size, $logoWidth);
            $coverage = ($width * $width) / max(1, $size * $size);
            if ($coverage > self::MAX_LOGO_COVERAGE) {
                $issues[] = sprintf(
                    'Logo covers ~%.0f%% of the QR area; keep it under %.0f%%.',
                    $coverage * 100,
                    self::MAX_LOGO_COVERAGE * 100,
                );
            }
        }

        if ($module === ModuleShape::Dots && $eye === EyeStyle::Pointy) {
            $issues[] = 'Dots modules with pointy eyes can reduce scanner compatibility; prefer circle or square eyes.';
        }

        if ($frame !== FrameStyle::None && $moduleMargin < self::MIN_MODULE_MARGIN) {
            $issues[] = 'Framed QR codes need a full quiet zone inside the frame.';
        }

        return $issues;
    }

    /**
     * @param  list<string>  $issues
     */
    public function assertSafe(array $issues): void
    {
        if ($issues === []) {
            return;
        }

        throw new ScanUnsafeException(
            'QR style failed scan-safe checks: '.implode(' ', $issues),
            $issues,
        );
    }
}
