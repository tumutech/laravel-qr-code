<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Enums;

use Endroid\QrCode\RoundBlockSizeMode as EndroidRoundBlockSizeMode;

enum RoundBlockSizeMode: string
{
    case Margin = 'margin';
    case Enlarge = 'enlarge';
    case Shrink = 'shrink';
    case None = 'none';

    public function toEndroid(): EndroidRoundBlockSizeMode
    {
        return match ($this) {
            self::Margin => EndroidRoundBlockSizeMode::Margin,
            self::Enlarge => EndroidRoundBlockSizeMode::Enlarge,
            self::Shrink => EndroidRoundBlockSizeMode::Shrink,
            self::None => EndroidRoundBlockSizeMode::None,
        };
    }
}
