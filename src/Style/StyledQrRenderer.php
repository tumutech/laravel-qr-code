<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Style;

use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Eye\PointyEye;
use BaconQrCode\Renderer\Eye\SimpleCircleEye;
use BaconQrCode\Renderer\Eye\SquareEye;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\DotsModule;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\Module\SquareModule;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use BaconQrCode\Renderer\RendererStyle\GradientType;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Tumutech\QrCode\Enums\ErrorCorrection;
use Tumutech\QrCode\Enums\EyeStyle;
use Tumutech\QrCode\Enums\Format;
use Tumutech\QrCode\Enums\FrameStyle;
use Tumutech\QrCode\Enums\GradientDirection;
use Tumutech\QrCode\Enums\ModuleShape;
use Tumutech\QrCode\Exceptions\QrCodeGenerationException;
use Tumutech\QrCode\QrCodeResult;

final class StyledQrRenderer
{
    /**
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     * @param  array{r: int, g: int, b: int, a: int}  $background
     * @param  array{start: array{r: int, g: int, b: int, a: int}, end: array{r: int, g: int, b: int, a: int}, direction: GradientDirection}|null  $gradient
     */
    public function render(
        string $data,
        Format $format,
        int $size,
        int $pixelMargin,
        ErrorCorrection $errorCorrection,
        string $encoding,
        ModuleShape $module,
        EyeStyle $eye,
        array $foreground,
        array $background,
        ?array $gradient,
        FrameStyle $frame,
        ?string $frameLabel,
        ?string $logoPath,
        ?int $logoWidth,
        bool $logoPunchout = true,
        float $moduleScale = 0.8,
        float $roundness = 0.5,
    ): QrCodeResult {
        if (! in_array($format, [Format::Png, Format::Svg], true)) {
            throw new QrCodeGenerationException('Styled QR codes currently support png and svg formats only.');
        }

        $moduleMargin = max(4, (int) round($pixelMargin / 4));
        $moduleScale = $this->clampUnit($moduleScale);
        $roundness = $this->clampUnit($roundness);
        $logoComposer = new LogoComposer;

        $contents = $format === Format::Svg
            ? $this->renderSvg(
                $data,
                $size,
                $moduleMargin,
                $errorCorrection,
                $encoding,
                $module,
                $eye,
                $foreground,
                $background,
                $gradient,
                $moduleScale,
                $roundness,
            )
            : $this->renderPng(
                $data,
                $size,
                $moduleMargin,
                $errorCorrection,
                $encoding,
                $module,
                $eye,
                $foreground,
                $background,
                $gradient,
                $logoPath,
                $logoWidth,
                $logoPunchout,
                $moduleScale,
                $roundness,
            );

        if ($format === Format::Svg && $logoPath !== null) {
            $contents = $logoComposer->applySvg(
                $contents,
                $logoPath,
                $size,
                $logoWidth,
                $background,
                $logoPunchout,
            );
        }

        if ($frame !== FrameStyle::None) {
            $contents = (new FrameComposer)->apply(
                $contents,
                $format,
                $frame,
                $frameLabel ?? 'SCAN ME',
                $size,
                $background,
                $foreground,
            );
        }

        $mime = $format->mimeType();

        return new QrCodeResult(
            $contents,
            $mime,
            $format,
            'data:'.$mime.';base64,'.base64_encode($contents),
        );
    }

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     * @param  array{r: int, g: int, b: int, a: int}  $background
     * @param  array{start: array{r: int, g: int, b: int, a: int}, end: array{r: int, g: int, b: int, a: int}, direction: GradientDirection}|null  $gradient
     */
    private function renderSvg(
        string $data,
        int $size,
        int $moduleMargin,
        ErrorCorrection $errorCorrection,
        string $encoding,
        ModuleShape $module,
        EyeStyle $eye,
        array $foreground,
        array $background,
        ?array $gradient,
        float $moduleScale,
        float $roundness,
    ): string {
        $style = new RendererStyle(
            $size,
            $moduleMargin,
            $this->baconModule($module, $moduleScale, $roundness),
            $this->baconEye($eye),
            $this->baconFill($foreground, $background, $gradient),
        );

        return (new Writer(new ImageRenderer($style, new SvgImageBackEnd)))
            ->writeString($data, $encoding, $errorCorrection->toBacon());
    }

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     * @param  array{r: int, g: int, b: int, a: int}  $background
     * @param  array{start: array{r: int, g: int, b: int, a: int}, end: array{r: int, g: int, b: int, a: int}, direction: GradientDirection}|null  $gradient
     */
    private function renderPng(
        string $data,
        int $size,
        int $moduleMargin,
        ErrorCorrection $errorCorrection,
        string $encoding,
        ModuleShape $module,
        EyeStyle $eye,
        array $foreground,
        array $background,
        ?array $gradient,
        ?string $logoPath,
        ?int $logoWidth,
        bool $logoPunchout,
        float $moduleScale,
        float $roundness,
    ): string {
        if (extension_loaded('imagick')) {
            $style = new RendererStyle(
                $size,
                $moduleMargin,
                $this->baconModule($module, $moduleScale, $roundness),
                $this->baconEye($eye),
                $this->baconFill($foreground, $background, $gradient),
            );
            $png = (new Writer(new ImageRenderer($style, new ImagickImageBackEnd)))
                ->writeString($data, $encoding, $errorCorrection->toBacon());
        } else {
            $png = $this->renderPngWithGd(
                $data,
                $size,
                $moduleMargin,
                $errorCorrection,
                $encoding,
                $module,
                $eye,
                $foreground,
                $background,
                $gradient,
                $moduleScale,
                $roundness,
            );
        }

        if ($logoPath !== null) {
            $png = (new LogoComposer)->applyPng(
                $png,
                $logoPath,
                $size,
                $logoWidth,
                $background,
                $logoPunchout,
            );
        }

        return $png;
    }

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     * @param  array{r: int, g: int, b: int, a: int}  $background
     * @param  array{start: array{r: int, g: int, b: int, a: int}, end: array{r: int, g: int, b: int, a: int}, direction: GradientDirection}|null  $gradient
     */
    private function renderPngWithGd(
        string $data,
        int $size,
        int $moduleMargin,
        ErrorCorrection $errorCorrection,
        string $encoding,
        ModuleShape $module,
        EyeStyle $eye,
        array $foreground,
        array $background,
        ?array $gradient,
        float $moduleScale,
        float $roundness,
    ): string {
        $qrCode = Encoder::encode($data, $errorCorrection->toBacon(), $encoding);
        $matrix = $qrCode->getMatrix();
        $matrixSize = $matrix->getWidth();
        $totalModules = $matrixSize + ($moduleMargin * 2);
        $moduleSize = $size / $totalModules;

        $image = imagecreatetruecolor($size, $size);
        if ($image === false) {
            throw new QrCodeGenerationException('Unable to create GD image for styled QR code.');
        }

        imagesavealpha($image, true);
        $bgColor = imagecolorallocate($image, $background['r'], $background['g'], $background['b']);
        imagefilledrectangle($image, 0, 0, $size, $size, $bgColor);

        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($this->isFinderRegion($x, $y, $matrixSize) || $matrix->get($x, $y) !== 1) {
                    continue;
                }

                $color = $this->sampleForeground($foreground, $gradient, $x, $y, $matrixSize);
                $fg = imagecolorallocate($image, $color['r'], $color['g'], $color['b']);
                $px = (int) round(($x + $moduleMargin) * $moduleSize);
                $py = (int) round(($y + $moduleMargin) * $moduleSize);
                $dim = max(1, (int) round($moduleSize));

                $this->drawModule($image, $module, $px, $py, $dim, $fg, $moduleScale, $roundness);
            }
        }

        $eyeColor = $this->sampleForeground($foreground, $gradient, 0, 0, $matrixSize);
        $fgEye = imagecolorallocate($image, $eyeColor['r'], $eyeColor['g'], $eyeColor['b']);

        foreach ($this->finderOrigins($matrixSize) as [$ox, $oy]) {
            $this->drawFinderEye(
                $image,
                $eye,
                ($ox + $moduleMargin) * $moduleSize,
                ($oy + $moduleMargin) * $moduleSize,
                $moduleSize,
                $fgEye,
                $bgColor,
            );
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);
        $png = ob_get_clean();

        if ($png === false) {
            throw new QrCodeGenerationException('Unable to encode styled PNG.');
        }

        return $png;
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    private function finderOrigins(int $matrixSize): array
    {
        return [
            [0, 0],
            [$matrixSize - 7, 0],
            [0, $matrixSize - 7],
        ];
    }

    private function isFinderRegion(int $x, int $y, int $matrixSize): bool
    {
        return ($x < 7 && $y < 7)
            || ($x >= $matrixSize - 7 && $y < 7)
            || ($x < 7 && $y >= $matrixSize - 7);
    }

    /**
     * Bacon-style finder: outer ring + inner pupil (square or circle).
     *
     * @param  \GdImage  $image
     */
    private function drawFinderEye(
        mixed $image,
        EyeStyle $eye,
        float $originX,
        float $originY,
        float $moduleSize,
        int $foreground,
        int $background,
    ): void {
        $x = (int) round($originX);
        $y = (int) round($originY);
        $outer = max(7, (int) round(7 * $moduleSize));
        $ring = max(1, (int) round($moduleSize));
        $innerOffset = $ring;
        $inner = max(1, $outer - (2 * $ring));
        $pupil = max(1, (int) round(3 * $moduleSize));
        $pupilOffset = (int) round(2 * $moduleSize);

        if ($eye === EyeStyle::Pointy) {
            imagefilledrectangle($image, $x, $y, $x + $outer - 1, $y + $outer - 1, $foreground);
            imagefilledellipse(
                $image,
                (int) round($x + ($outer / 2)),
                (int) round($y + ($outer / 2)),
                (int) round(5 * $moduleSize),
                (int) round(5 * $moduleSize),
                $background,
            );
            imagefilledellipse(
                $image,
                (int) round($x + ($outer / 2)),
                (int) round($y + ($outer / 2)),
                $pupil,
                $pupil,
                $foreground,
            );

            return;
        }

        imagefilledrectangle($image, $x, $y, $x + $outer - 1, $y + $outer - 1, $foreground);
        imagefilledrectangle(
            $image,
            $x + $innerOffset,
            $y + $innerOffset,
            $x + $innerOffset + $inner - 1,
            $y + $innerOffset + $inner - 1,
            $background,
        );

        if ($eye === EyeStyle::Circle) {
            imagefilledellipse(
                $image,
                (int) round($x + ($outer / 2)),
                (int) round($y + ($outer / 2)),
                $pupil,
                $pupil,
                $foreground,
            );

            return;
        }

        imagefilledrectangle(
            $image,
            $x + $pupilOffset,
            $y + $pupilOffset,
            $x + $pupilOffset + $pupil - 1,
            $y + $pupilOffset + $pupil - 1,
            $foreground,
        );
    }

    /**
     * @param  \GdImage  $image
     */
    private function drawModule(
        mixed $image,
        ModuleShape $shape,
        int $x,
        int $y,
        int $size,
        int $color,
        float $moduleScale,
        float $roundness,
    ): void {
        match ($shape) {
            ModuleShape::Square => imagefilledrectangle($image, $x, $y, $x + $size - 1, $y + $size - 1, $color),
            ModuleShape::Dots => imagefilledellipse(
                $image,
                (int) round($x + ($size / 2)),
                (int) round($y + ($size / 2)),
                max(1, (int) round($size * $moduleScale)),
                max(1, (int) round($size * $moduleScale)),
                $color,
            ),
            ModuleShape::Rounded => $this->filledRoundedRectangle(
                $image,
                $x,
                $y,
                $size,
                $color,
                max(0.15, $roundness),
            ),
        };
    }

    /**
     * @param  \GdImage  $image
     */
    private function filledRoundedRectangle(mixed $image, int $x, int $y, int $size, int $color, float $intensity): void
    {
        $radius = max(1, (int) round($size * 0.5 * $intensity));
        imagefilledrectangle($image, $x + $radius, $y, $x + $size - $radius - 1, $y + $size - 1, $color);
        imagefilledrectangle($image, $x, $y + $radius, $x + $size - 1, $y + $size - $radius - 1, $color);
        imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $size - $radius - 1, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $radius, $y + $size - $radius - 1, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $size - $radius - 1, $y + $size - $radius - 1, $radius * 2, $radius * 2, $color);
    }

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     * @param  array{start: array{r: int, g: int, b: int, a: int}, end: array{r: int, g: int, b: int, a: int}, direction: GradientDirection}|null  $gradient
     * @return array{r: int, g: int, b: int, a: int}
     */
    private function sampleForeground(array $foreground, ?array $gradient, int $x, int $y, int $matrixSize): array
    {
        if ($gradient === null) {
            return $foreground;
        }

        $t = match ($gradient['direction']) {
            GradientDirection::Horizontal => $matrixSize > 1 ? $x / ($matrixSize - 1) : 0.0,
            GradientDirection::Vertical => $matrixSize > 1 ? $y / ($matrixSize - 1) : 0.0,
            GradientDirection::Radial => sqrt(
                (($x - (($matrixSize - 1) / 2)) ** 2) + (($y - (($matrixSize - 1) / 2)) ** 2),
            ) / max(1.0, ($matrixSize - 1) / 2),
            GradientDirection::InverseDiagonal => $matrixSize > 1
                ? (($matrixSize - 1 - $x) + $y) / (2 * ($matrixSize - 1))
                : 0.0,
            GradientDirection::Diagonal => $matrixSize > 1
                ? ($x + $y) / (2 * ($matrixSize - 1))
                : 0.0,
        };

        $t = max(0.0, min(1.0, $t));
        $start = $gradient['start'];
        $end = $gradient['end'];

        return [
            'r' => (int) round($start['r'] + (($end['r'] - $start['r']) * $t)),
            'g' => (int) round($start['g'] + (($end['g'] - $start['g']) * $t)),
            'b' => (int) round($start['b'] + (($end['b'] - $start['b']) * $t)),
            'a' => 0,
        ];
    }

    /**
     * @param  array{r: int, g: int, b: int, a: int}  $foreground
     * @param  array{r: int, g: int, b: int, a: int}  $background
     * @param  array{start: array{r: int, g: int, b: int, a: int}, end: array{r: int, g: int, b: int, a: int}, direction: GradientDirection}|null  $gradient
     */
    private function baconFill(array $foreground, array $background, ?array $gradient): Fill
    {
        $bg = new Rgb($background['r'], $background['g'], $background['b']);

        if ($gradient === null) {
            return Fill::uniformColor($bg, new Rgb($foreground['r'], $foreground['g'], $foreground['b']));
        }

        return Fill::uniformGradient($bg, new Gradient(
            new Rgb($gradient['start']['r'], $gradient['start']['g'], $gradient['start']['b']),
            new Rgb($gradient['end']['r'], $gradient['end']['g'], $gradient['end']['b']),
            $this->baconGradientType($gradient['direction']),
        ));
    }

    private function baconGradientType(GradientDirection $direction): GradientType
    {
        return match ($direction) {
            GradientDirection::Vertical => GradientType::VERTICAL(),
            GradientDirection::Horizontal => GradientType::HORIZONTAL(),
            GradientDirection::Diagonal => GradientType::DIAGONAL(),
            GradientDirection::InverseDiagonal => GradientType::INVERSE_DIAGONAL(),
            GradientDirection::Radial => GradientType::RADIAL(),
        };
    }

    private function baconModule(ModuleShape $module, float $moduleScale, float $roundness): SquareModule|DotsModule|RoundnessModule
    {
        return match ($module) {
            ModuleShape::Square => SquareModule::instance(),
            ModuleShape::Dots => new DotsModule($moduleScale),
            ModuleShape::Rounded => new RoundnessModule($roundness),
        };
    }

    private function baconEye(EyeStyle $eye): SquareEye|SimpleCircleEye|PointyEye
    {
        return match ($eye) {
            EyeStyle::Square => SquareEye::instance(),
            EyeStyle::Circle => SimpleCircleEye::instance(),
            EyeStyle::Pointy => PointyEye::instance(),
        };
    }

    private function clampUnit(float $value): float
    {
        if ($value <= 0 || $value > 1) {
            throw new QrCodeGenerationException('Module scale/roundness must be between 0 (exclusive) and 1 (inclusive).');
        }

        return $value;
    }
}
