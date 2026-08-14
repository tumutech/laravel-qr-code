<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Tests\Unit;

use Tumutech\QrCode\Exceptions\ScanUnsafeException;
use Tumutech\QrCode\QrCodeBuilder;
use Tumutech\QrCode\Style\StyleTemplates;
use Tumutech\QrCode\Tests\TestCase;

final class StyledQrCodeTest extends TestCase
{
    public function test_dots_module_generates_png(): void
    {
        $result = $this->builder()
            ->module('dots')
            ->eye('circle')
            ->color('#0f766e')
            ->backgroundColor('#ffffff')
            ->scanSafe()
            ->generate('https://example.com/dots');

        $this->assertStringStartsWith("\x89PNG", $result->getString());
        $this->assertSame('image/png', $result->getMimeType());
    }

    public function test_gradient_svg_contains_svg_markup(): void
    {
        $result = $this->builder()
            ->format('svg')
            ->module('dots')
            ->eye('circle')
            ->gradient('#0e7490', '#134e4a')
            ->generate('https://example.com/gradient');

        $this->assertStringContainsString('<svg', $result->getString());
    }

    public function test_template_applies_preset(): void
    {
        $result = $this->builder()
            ->template('dot-teal')
            ->generate('https://example.com/template');

        $this->assertStringStartsWith("\x89PNG", $result->getString());
    }

    public function test_frame_badge_generates_larger_png(): void
    {
        $plain = $this->builder()->size(200)->generate('framed-plain');
        $framed = $this->builder()->size(200)->frame('badge', 'SCAN ME')->generate('framed-badge');

        $this->assertGreaterThan(strlen($plain->getString()), strlen($framed->getString()));
    }

    public function test_all_style_templates_generate(): void
    {
        $templates = (new StyleTemplates)->names();

        $this->assertNotEmpty($templates);

        foreach ($templates as $name) {
            $result = $this->builder()
                ->size(180)
                ->template($name)
                ->scanSafe(strict: false)
                ->generate('https://example.com/'.$name);

            $this->assertNotSame('', $result->getString(), $name);
        }
    }

    public function test_dots_with_circle_eyes_keeps_finder_structure(): void
    {
        $result = $this->builder()
            ->module('dots', 0.8)
            ->eye('circle')
            ->color('#111827')
            ->backgroundColor('#ffffff')
            ->scanSafe()
            ->generate('https://example.com/finder-eyes');

        $this->assertStringStartsWith("\x89PNG", $result->getString());
    }

    public function test_scan_safe_rejects_low_contrast(): void
    {
        $this->expectException(ScanUnsafeException::class);

        $this->builder()
            ->color('#eeeeee')
            ->backgroundColor('#ffffff')
            ->module('dots')
            ->scanSafe()
            ->generate('https://example.com/unsafe');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function builder(array $overrides = []): QrCodeBuilder
    {
        /** @var array<string, mixed> $config */
        $config = array_merge(config('qr-code'), $overrides);

        return new QrCodeBuilder($config);
    }
}
