<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Enums;

use Endroid\QrCode\ErrorCorrectionLevel;

enum ErrorCorrection: string
{
    case Low = 'low';
    case Medium = 'medium';
    case Quartile = 'quartile';
    case High = 'high';

    public function toEndroid(): ErrorCorrectionLevel
    {
        return match ($this) {
            self::Low => ErrorCorrectionLevel::Low,
            self::Medium => ErrorCorrectionLevel::Medium,
            self::Quartile => ErrorCorrectionLevel::Quartile,
            self::High => ErrorCorrectionLevel::High,
        };
    }

    public function toBacon(): \BaconQrCode\Common\ErrorCorrectionLevel
    {
        return match ($this) {
            self::Low => \BaconQrCode\Common\ErrorCorrectionLevel::L(),
            self::Medium => \BaconQrCode\Common\ErrorCorrectionLevel::M(),
            self::Quartile => \BaconQrCode\Common\ErrorCorrectionLevel::Q(),
            self::High => \BaconQrCode\Common\ErrorCorrectionLevel::H(),
        };
    }
}
