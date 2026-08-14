<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Enums;

enum ModuleShape: string
{
    case Square = 'square';
    case Dots = 'dots';
    case Rounded = 'rounded';
}
