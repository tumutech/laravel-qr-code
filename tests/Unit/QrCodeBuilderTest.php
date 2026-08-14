<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Tests\Unit;

use Tumutech\QrCode\Enums\Format;
use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;
use Tumutech\QrCode\QrCodeBuilder;
use Tumutech\QrCode\Tests\TestCase;

final class QrCodeBuilderTest extends TestCase
{
    public function test_generate_png_returns_png_binary(): void
    {
        $result = $this->builder()->format('png')->generate('https://example.com');

        $this->assertSame(Format::Png, $result->getFormat());
        $this->assertSame('image/png', $result->getMimeType());
        $this->assertStringStartsWith("\x89PNG", $result->getString());
        $this->assertStringStartsWith('data:image/png;base64,', $result->getDataUri());
    }

    public function test_generate_svg_returns_svg_markup(): void
    {
        $result = $this->builder()->format('svg')->generate('https://example.com');

        $this->assertSame(Format::Svg, $result->getFormat());
        $this->assertSame('image/svg+xml', $result->getMimeType());
        $this->assertStringContainsString('<svg', $result->getString());
    }

    public function test_builder_is_immutable(): void
    {
        $base = $this->builder()->size(100);
        $larger = $base->size(400);

        $small = $base->generate('immutable');
        $big = $larger->generate('immutable');

        $this->assertNotSame(strlen($small->getString()), strlen($big->getString()));
    }

    public function test_empty_data_is_rejected(): void
    {
        $this->expectException(InvalidQrCodeOptionException::class);

        $this->builder()->generate('');
    }

    public function test_oversized_data_is_rejected(): void
    {
        $this->expectException(InvalidQrCodeOptionException::class);

        $this->builder(['max_data_length' => 8])->generate('too-long-payload');
    }

    public function test_wifi_helper_generates_image(): void
    {
        $result = $this->builder()->wifi([
            'ssid' => 'Office',
            'password' => 'secret',
        ]);

        $this->assertStringStartsWith("\x89PNG", $result->getString());
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
