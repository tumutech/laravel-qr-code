<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Data;

interface QrPayload
{
    public function toString(): string;
}
