<?php

declare(strict_types=1);

namespace Tumutech\QrCode;

use Tumutech\QrCode\Enums\Format;

final class QrCodeResult
{
    public function __construct(
        private readonly string $contents,
        private readonly string $mimeType,
        private readonly Format $format,
        private readonly string $dataUri,
    ) {}

    public function getString(): string
    {
        return $this->contents;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getFormat(): Format
    {
        return $this->format;
    }

    public function getDataUri(): string
    {
        return $this->dataUri;
    }

    public function saveToFile(string $path): void
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new Exceptions\QrCodeGenerationException("Unable to create directory [{$directory}].");
        }

        if (file_put_contents($path, $this->contents) === false) {
            throw new Exceptions\QrCodeGenerationException("Unable to write QR code to [{$path}].");
        }
    }

    public function __toString(): string
    {
        return $this->contents;
    }
}
