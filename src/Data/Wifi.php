<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Data;

use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;

final class Wifi implements QrPayload
{
    public function __construct(
        public readonly string $ssid,
        public readonly ?string $password = null,
        public readonly string $encryption = 'WPA',
        public readonly bool $hidden = false,
    ) {
        if ($ssid === '') {
            throw new InvalidQrCodeOptionException('Wi-Fi SSID cannot be empty.');
        }

        if (! in_array(strtoupper($encryption), ['WPA', 'WEP', 'NOPASS'], true)) {
            throw new InvalidQrCodeOptionException('Wi-Fi encryption must be WPA, WEP, or nopass.');
        }
    }

    public static function make(
        string $ssid,
        ?string $password = null,
        string $encryption = 'WPA',
        bool $hidden = false,
    ): self {
        return new self($ssid, $password, $encryption, $hidden);
    }

    public function toString(): string
    {
        $encryption = strtoupper($this->encryption) === 'NOPASS' ? 'nopass' : strtoupper($this->encryption);

        $parts = [
            'T:'.$encryption,
            'S:'.$this->escape($this->ssid),
        ];

        if ($encryption !== 'nopass' && $this->password !== null && $this->password !== '') {
            $parts[] = 'P:'.$this->escape($this->password);
        }

        if ($this->hidden) {
            $parts[] = 'H:true';
        }

        return 'WIFI:'.implode(';', $parts).';;';
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', ':', '"'],
            ['\\\\', '\\;', '\\,', '\\:', '\\"'],
            $value,
        );
    }
}
