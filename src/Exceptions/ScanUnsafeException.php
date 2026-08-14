<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Exceptions;

use RuntimeException;

final class ScanUnsafeException extends RuntimeException
{
    /**
     * @param  list<string>  $issues
     */
    public function __construct(
        string $message,
        public readonly array $issues = [],
    ) {
        parent::__construct($message);
    }
}
