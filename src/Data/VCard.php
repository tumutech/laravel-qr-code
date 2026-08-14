<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Data;

use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;

final class VCard implements QrPayload
{
    /**
     * @param  list<string>  $phones
     * @param  list<string>  $emails
     */
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName = '',
        public readonly ?string $organization = null,
        public readonly ?string $title = null,
        public readonly array $phones = [],
        public readonly array $emails = [],
        public readonly ?string $url = null,
        public readonly ?string $note = null,
    ) {
        if ($firstName === '' && $lastName === '') {
            throw new InvalidQrCodeOptionException('vCard requires at least a first or last name.');
        }
    }

    /**
     * @param  array{
     *     firstName?: string,
     *     lastName?: string,
     *     organization?: string|null,
     *     title?: string|null,
     *     phones?: list<string>,
     *     emails?: list<string>,
     *     url?: string|null,
     *     note?: string|null
     * }  $attributes
     */
    public static function make(array $attributes): self
    {
        return new self(
            firstName: $attributes['firstName'] ?? '',
            lastName: $attributes['lastName'] ?? '',
            organization: $attributes['organization'] ?? null,
            title: $attributes['title'] ?? null,
            phones: $attributes['phones'] ?? [],
            emails: $attributes['emails'] ?? [],
            url: $attributes['url'] ?? null,
            note: $attributes['note'] ?? null,
        );
    }

    public function toString(): string
    {
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:'.$this->escape($this->lastName).';'.$this->escape($this->firstName).';;;',
            'FN:'.$this->escape(trim($this->firstName.' '.$this->lastName)),
        ];

        if ($this->organization !== null && $this->organization !== '') {
            $lines[] = 'ORG:'.$this->escape($this->organization);
        }

        if ($this->title !== null && $this->title !== '') {
            $lines[] = 'TITLE:'.$this->escape($this->title);
        }

        foreach ($this->phones as $phone) {
            $lines[] = 'TEL;TYPE=CELL:'.$this->escape($phone);
        }

        foreach ($this->emails as $email) {
            $lines[] = 'EMAIL;TYPE=INTERNET:'.$this->escape($email);
        }

        if ($this->url !== null && $this->url !== '') {
            $lines[] = 'URL:'.$this->escape($this->url);
        }

        if ($this->note !== null && $this->note !== '') {
            $lines[] = 'NOTE:'.$this->escape($this->note);
        }

        $lines[] = 'END:VCARD';

        return implode("\n", $lines);
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\\;', '\\,', '\\n'],
            $value,
        );
    }
}
