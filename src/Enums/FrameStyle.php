<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Enums;

enum FrameStyle: string
{
    case None = 'none';
    case Badge = 'badge';
    case Card = 'card';
}
