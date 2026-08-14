<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Enums;

enum Format: string
{
    case Png = 'png';
    case Svg = 'svg';
    case Webp = 'webp';
    case Eps = 'eps';
    case Pdf = 'pdf';
    case Binary = 'binary';

    public function mimeType(): string
    {
        return match ($this) {
            self::Png => 'image/png',
            self::Svg => 'image/svg+xml',
            self::Webp => 'image/webp',
            self::Eps => 'application/postscript',
            self::Pdf => 'application/pdf',
            self::Binary => 'application/octet-stream',
        };
    }

    public function extension(): string
    {
        return $this->value;
    }
}
