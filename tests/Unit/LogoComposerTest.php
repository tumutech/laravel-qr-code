<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Tests\Unit;

use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;
use Tumutech\QrCode\Exceptions\ScanUnsafeException;
use Tumutech\QrCode\QrCodeBuilder;
use Tumutech\QrCode\Style\LogoComposer;
use Tumutech\QrCode\Tests\TestCase;

final class LogoComposerTest extends TestCase
{
    private string $logoPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logoPath = sys_get_temp_dir().'/tumutech-qr-logo-'.uniqid('', true).'.png';
        $image = imagecreatetruecolor(64, 64);
        $green = imagecolorallocate($image, 37, 211, 102);
        imagefilledrectangle($image, 0, 0, 63, 63, $green);
        imagepng($image, $this->logoPath);
        imagedestroy($image);
    }

    protected function tearDown(): void
    {
        if (is_file($this->logoPath)) {
            unlink($this->logoPath);
        }

        parent::tearDown();
    }

    public function test_center_logo_on_plain_png(): void
    {
        $result = $this->builder()
            ->size(280)
            ->logo($this->logoPath)
            ->scanSafe()
            ->generate('https://example.com/branded');

        $this->assertStringStartsWith("\x89PNG", $result->getString());
    }

    public function test_center_logo_on_styled_png_and_svg(): void
    {
        $png = $this->builder()
            ->size(280)
            ->module('dots', 0.8)
            ->eye('circle')
            ->color('#101824')
            ->backgroundColor('#ffffff')
            ->logo($this->logoPath)
            ->scanSafe()
            ->generate('https://example.com/styled-logo');

        $svg = $this->builder()
            ->format('svg')
            ->size(280)
            ->module('dots', 0.8)
            ->eye('circle')
            ->logo($this->logoPath)
            ->scanSafe()
            ->generate('https://example.com/styled-logo-svg');

        $this->assertStringStartsWith("\x89PNG", $png->getString());
        $this->assertStringContainsString('<image href=', $svg->getString());
        $this->assertStringContainsString('<rect ', $svg->getString());
    }

    public function test_default_logo_width_scales_with_qr_size(): void
    {
        $composer = new LogoComposer;

        $this->assertSame(62, $composer->resolveWidth(280, null));
        $this->assertSame(80, $composer->resolveWidth(280, 80));
    }

    public function test_oversized_logo_fails_scan_safe(): void
    {
        $this->expectException(ScanUnsafeException::class);

        $this->builder()
            ->size(200)
            ->logo($this->logoPath, 120)
            ->scanSafe()
            ->generate('https://example.com/too-big-logo');
    }

    public function test_missing_logo_is_rejected(): void
    {
        $this->expectException(InvalidQrCodeOptionException::class);

        $this->builder()->logo('/tmp/does-not-exist-logo.png');
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
