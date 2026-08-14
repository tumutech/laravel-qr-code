<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tumutech\QrCode\Data\Email;
use Tumutech\QrCode\Data\Sms;
use Tumutech\QrCode\Data\VCard;
use Tumutech\QrCode\Data\Wifi;
use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;

final class PayloadTest extends TestCase
{
    public function test_wifi_payload_format(): void
    {
        $wifi = Wifi::make('Office', 'secret', 'WPA');

        $this->assertSame('WIFI:T:WPA;S:Office;P:secret;;', $wifi->toString());
    }

    public function test_wifi_escapes_special_characters(): void
    {
        $wifi = Wifi::make('Cafe;Main', 'p:ass,word');

        $this->assertSame('WIFI:T:WPA;S:Cafe\\;Main;P:p\\:ass\\,word;;', $wifi->toString());
    }

    public function test_wifi_rejects_empty_ssid(): void
    {
        $this->expectException(InvalidQrCodeOptionException::class);

        Wifi::make('');
    }

    public function test_email_payload(): void
    {
        $email = Email::make('hello@example.com', 'Hi', 'Body');

        $this->assertSame(
            'mailto:hello@example.com?subject=Hi&body=Body',
            $email->toString(),
        );
    }

    public function test_sms_payload(): void
    {
        $sms = Sms::make('+15551234567', 'Hello world');

        $this->assertSame('sms:+15551234567?body=Hello%20world', $sms->toString());
    }

    public function test_vcard_payload_contains_required_fields(): void
    {
        $vcard = VCard::make([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'organization' => 'Analytical Engines',
            'emails' => ['ada@example.com'],
            'phones' => ['+15550001111'],
        ]);

        $payload = $vcard->toString();

        $this->assertStringContainsString('BEGIN:VCARD', $payload);
        $this->assertStringContainsString('FN:Ada Lovelace', $payload);
        $this->assertStringContainsString('ORG:Analytical Engines', $payload);
        $this->assertStringContainsString('EMAIL;TYPE=INTERNET:ada@example.com', $payload);
        $this->assertStringContainsString('END:VCARD', $payload);
    }
}
