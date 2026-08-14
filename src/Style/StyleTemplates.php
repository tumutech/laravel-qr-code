<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Style;

use Tumutech\QrCode\Enums\ErrorCorrection;
use Tumutech\QrCode\Enums\EyeStyle;
use Tumutech\QrCode\Enums\FrameStyle;
use Tumutech\QrCode\Enums\GradientDirection;
use Tumutech\QrCode\Enums\ModuleShape;
use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;
use Tumutech\QrCode\Support\ColorParser;

final class StyleTemplates
{
    /**
     * @return array{
     *     module: ModuleShape,
     *     eye: EyeStyle,
     *     foreground: array{r: int, g: int, b: int, a: int},
     *     background: array{r: int, g: int, b: int, a: int},
     *     gradient?: array{start: array{r: int, g: int, b: int, a: int}, end: array{r: int, g: int, b: int, a: int}, direction: GradientDirection},
     *     frame: FrameStyle,
     *     frame_label?: string,
     *     error_correction: ErrorCorrection,
     *     module_scale?: float,
     *     roundness?: float
     * }
     */
    public function get(string $name): array
    {
        $templates = $this->all();

        if (! isset($templates[$name])) {
            throw new InvalidQrCodeOptionException(
                "Unknown style template [{$name}]. Available: ".implode(', ', array_keys($templates)),
            );
        }

        return $templates[$name];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->all());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            'classic' => $this->base(ModuleShape::Square, EyeStyle::Square, '#000000', '#ffffff'),
            'classic-inverted' => $this->base(ModuleShape::Square, EyeStyle::Square, '#f8fafc', '#0f172a', ErrorCorrection::High),

            'dots-circle' => $this->base(ModuleShape::Dots, EyeStyle::Circle, '#0f766e', '#f8fafc', ErrorCorrection::High, moduleScale: 0.8),
            'dots-square-eyes' => $this->base(ModuleShape::Dots, EyeStyle::Square, '#111827', '#ffffff', ErrorCorrection::High, moduleScale: 0.8),
            'dots-pointy' => $this->base(ModuleShape::Dots, EyeStyle::Pointy, '#7c2d12', '#fff7ed', ErrorCorrection::High, moduleScale: 0.75),
            'dots-small' => $this->base(ModuleShape::Dots, EyeStyle::Circle, '#1d4ed8', '#eff6ff', ErrorCorrection::High, moduleScale: 0.6),
            'dots-large' => $this->base(ModuleShape::Dots, EyeStyle::Circle, '#14532d', '#f0fdf4', ErrorCorrection::High, moduleScale: 1.0),

            'rounded-soft' => $this->base(ModuleShape::Rounded, EyeStyle::Square, '#1c1917', '#fafaf9', ErrorCorrection::Quartile, roundness: 0.25),
            'rounded-medium' => $this->base(ModuleShape::Rounded, EyeStyle::Circle, '#334155', '#f8fafc', ErrorCorrection::High, roundness: 0.5),
            'rounded-strong' => $this->base(ModuleShape::Rounded, EyeStyle::Pointy, '#3f1d0b', '#fffbeb', ErrorCorrection::High, roundness: 1.0),

            'gradient-ocean' => $this->withGradient(
                $this->base(ModuleShape::Dots, EyeStyle::Circle, '#0e7490', '#ecfeff', ErrorCorrection::High, moduleScale: 0.8),
                '#0e7490',
                '#134e4a',
                GradientDirection::Diagonal,
            ),
            'gradient-sunset' => $this->withGradient(
                $this->base(ModuleShape::Dots, EyeStyle::Circle, '#ea580c', '#fff7ed', ErrorCorrection::High, moduleScale: 0.8),
                '#ea580c',
                '#9f1239',
                GradientDirection::Vertical,
            ),
            'gradient-aurora' => $this->withGradient(
                $this->base(ModuleShape::Rounded, EyeStyle::Circle, '#7c3aed', '#f5f3ff', ErrorCorrection::High, roundness: 0.5),
                '#7c3aed',
                '#0ea5e9',
                GradientDirection::Radial,
            ),
            'gradient-forest' => $this->withGradient(
                $this->base(ModuleShape::Square, EyeStyle::Square, '#166534', '#f0fdf4', ErrorCorrection::High),
                '#166534',
                '#052e16',
                GradientDirection::Horizontal,
            ),
            'gradient-inverse' => $this->withGradient(
                $this->base(ModuleShape::Dots, EyeStyle::Pointy, '#0369a1', '#f0f9ff', ErrorCorrection::High, moduleScale: 0.85),
                '#0369a1',
                '#0f172a',
                GradientDirection::InverseDiagonal,
            ),

            'pointy-brand' => $this->base(ModuleShape::Square, EyeStyle::Pointy, '#111827', '#ffffff', ErrorCorrection::High),
            'circle-eyes-square' => $this->base(ModuleShape::Square, EyeStyle::Circle, '#0f172a', '#ffffff', ErrorCorrection::High),

            'badge-brand' => array_merge(
                $this->base(ModuleShape::Square, EyeStyle::Square, '#111827', '#ffffff', ErrorCorrection::High),
                ['frame' => FrameStyle::Badge, 'frame_label' => 'SCAN ME'],
            ),
            'card-dots' => array_merge(
                $this->base(ModuleShape::Dots, EyeStyle::Circle, '#0f766e', '#ffffff', ErrorCorrection::High, moduleScale: 0.8),
                ['frame' => FrameStyle::Card, 'frame_label' => 'OPEN LINK'],
            ),

            // aliases kept for earlier demos
            'dot-teal' => $this->base(ModuleShape::Dots, EyeStyle::Circle, '#0f766e', '#f8fafc', ErrorCorrection::High, moduleScale: 0.8),
            'rounded-ink' => $this->base(ModuleShape::Rounded, EyeStyle::Square, '#1c1917', '#fafaf9', ErrorCorrection::Quartile, roundness: 0.5),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function base(
        ModuleShape $module,
        EyeStyle $eye,
        string $foreground,
        string $background,
        ErrorCorrection $errorCorrection = ErrorCorrection::Medium,
        float $moduleScale = 0.8,
        float $roundness = 0.5,
    ): array {
        return [
            'module' => $module,
            'eye' => $eye,
            'foreground' => ColorParser::parse($foreground),
            'background' => ColorParser::parse($background),
            'frame' => FrameStyle::None,
            'error_correction' => $errorCorrection,
            'module_scale' => $moduleScale,
            'roundness' => $roundness,
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    private function withGradient(
        array $base,
        string $start,
        string $end,
        GradientDirection $direction,
    ): array {
        $base['gradient'] = [
            'start' => ColorParser::parse($start),
            'end' => ColorParser::parse($end),
            'direction' => $direction,
        ];
        $base['foreground'] = $base['gradient']['start'];

        return $base;
    }
}
