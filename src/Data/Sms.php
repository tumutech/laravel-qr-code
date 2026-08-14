<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Data;

use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;

final class Sms implements QrPayload
{
    public function __construct(
        public readonly string $number,
        public readonly ?string $message = null,
    ) {
        if ($number === '') {
            throw new InvalidQrCodeOptionException('SMS number cannot be empty.');
        }
    }

    public static function make(string $number, ?string $message = null): self
    {
        return new self($number, $message);
    }

    public function toString(): string
    {
        if ($this->message === null || $this->message === '') {
            return 'sms:'.$this->number;
        }

        return 'sms:'.$this->number.'?body='.rawurlencode($this->message);
    }
}
