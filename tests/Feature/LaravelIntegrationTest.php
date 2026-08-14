<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tumutech\QrCode\Facades\QrCode;
use Tumutech\QrCode\Tests\TestCase;

final class LaravelIntegrationTest extends TestCase
{
    public function test_provider_merges_config(): void
    {
        $this->assertSame('png', config('qr-code.format'));
        $this->assertSame(200, config('qr-code.size'));
    }

    public function test_facade_generates_png_response(): void
    {
        $response = QrCode::png('https://example.com/facade');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_facade_generates_svg_response(): void
    {
        $response = QrCode::svg('https://example.com/svg');

        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_store_writes_to_disk(): void
    {
        Storage::fake('local');

        $path = QrCode::format('png')->store('https://example.com/store', 'qrs/ticket.png');

        $this->assertSame('qrs/ticket.png', $path);
        Storage::disk('local')->assertExists('qrs/ticket.png');
        $this->assertStringStartsWith("\x89PNG", Storage::disk('local')->get('qrs/ticket.png'));
    }

    public function test_blade_component_renders_image(): void
    {
        $html = (string) $this->blade(
            '<x-qr-code data="https://example.com/blade" :size="180" class="qr" />'
        );

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('width="180"', $html);
        $this->assertStringContainsString('class="qr"', $html);
        $this->assertStringContainsString('alt="QR code"', $html);
    }

    public function test_blade_component_accepts_center_logo(): void
    {
        $logo = sys_get_temp_dir().'/tumutech-blade-logo-'.uniqid('', true).'.png';
        $image = imagecreatetruecolor(48, 48);
        $color = imagecolorallocate($image, 250, 81, 15);
        imagefilledrectangle($image, 0, 0, 47, 47, $color);
        imagepng($image, $logo);
        imagedestroy($image);

        try {
            $html = (string) $this->blade(
                '<x-qr-code data="https://example.com/logo" :size="220" :logo="$logo" />',
                ['logo' => $logo],
            );

            $this->assertStringContainsString('data:image/png;base64,', $html);
        } finally {
            if (is_file($logo)) {
                unlink($logo);
            }
        }
    }

    public function test_qrcode_blade_directive_outputs_data_uri(): void
    {
        $html = (string) $this->blade("@qrcode('https://example.com/directive')");

        $this->assertStringStartsWith('data:image/png;base64,', $html);
    }
}
