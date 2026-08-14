<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Data;

use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;

final class Email implements QrPayload
{
    public function __construct(
        public readonly string $address,
        public readonly ?string $subject = null,
        public readonly ?string $body = null,
    ) {
        if ($address === '' || ! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidQrCodeOptionException('A valid email address is required.');
        }
    }

    public static function make(string $address, ?string $subject = null, ?string $body = null): self
    {
        return new self($address, $subject, $body);
    }

    public function toString(): string
    {
        $query = array_filter([
            'subject' => $this->subject,
            'body' => $this->body,
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        if ($query === []) {
            return 'mailto:'.$this->address;
        }

        return 'mailto:'.$this->address.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
