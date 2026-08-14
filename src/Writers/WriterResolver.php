<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Writers;

use Endroid\QrCode\Writer\BinaryWriter;
use Endroid\QrCode\Writer\EpsWriter;
use Endroid\QrCode\Writer\PdfWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WebPWriter;
use Endroid\QrCode\Writer\WriterInterface;
use Tumutech\QrCode\Enums\Format;

final class WriterResolver
{
    public function resolve(Format $format): WriterInterface
    {
        return match ($format) {
            Format::Png => new PngWriter,
            Format::Svg => new SvgWriter,
            Format::Webp => new WebPWriter,
            Format::Eps => new EpsWriter,
            Format::Pdf => new PdfWriter,
            Format::Binary => new BinaryWriter,
        };
    }
}
