<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Enums;

enum GradientDirection: string
{
    case Vertical = 'vertical';
    case Horizontal = 'horizontal';
    case Diagonal = 'diagonal';
    case InverseDiagonal = 'inverse_diagonal';
    case Radial = 'radial';
}
