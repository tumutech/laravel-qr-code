<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Facades;

use Illuminate\Support\Facades\Facade;
use Tumutech\QrCode\QrCodeBuilder;
use Tumutech\QrCode\QrCodeFactory;
use Tumutech\QrCode\QrCodeResult;

/**
 * @method static QrCodeBuilder builder()
 * @method static QrCodeBuilder format(\Tumutech\QrCode\Enums\Format|string $format)
 * @method static QrCodeBuilder size(int $size)
 * @method static QrCodeBuilder margin(int $margin)
 * @method static QrCodeBuilder errorCorrection(\Tumutech\QrCode\Enums\ErrorCorrection|string $level)
 * @method static QrCodeBuilder encoding(string $encoding)
 * @method static QrCodeBuilder color(int|string $r, ?int $g = null, ?int $b = null, int $a = 0)
 * @method static QrCodeBuilder backgroundColor(int|string $r, ?int $g = null, ?int $b = null, int $a = 0)
 * @method static QrCodeBuilder module(\Tumutech\QrCode\Enums\ModuleShape|string $shape, ?float $scale = null)
 * @method static QrCodeBuilder moduleScale(float $scale)
 * @method static QrCodeBuilder roundness(float $intensity)
 * @method static QrCodeBuilder eye(\Tumutech\QrCode\Enums\EyeStyle|string $style)
 * @method static QrCodeBuilder gradient(string $start, string $end, \Tumutech\QrCode\Enums\GradientDirection|string $direction = 'diagonal')
 * @method static QrCodeBuilder frame(\Tumutech\QrCode\Enums\FrameStyle|string $style = 'badge', ?string $label = 'SCAN ME')
 * @method static QrCodeBuilder template(string $name)
 * @method static QrCodeBuilder scanSafe(bool $enabled = true, bool $strict = true)
 * @method static list<string> scanSafeIssues()
 * @method static QrCodeBuilder logo(string $path, ?int $width = null, bool $punchoutBackground = true)
 * @method static QrCodeBuilder label(string $text)
 * @method static QrCodeBuilder disk(?string $disk)
 * @method static QrCodeResult generate(string|\Tumutech\QrCode\Data\QrPayload $data)
 * @method static string dataUri(string|\Tumutech\QrCode\Data\QrPayload $data)
 * @method static \Illuminate\Http\Response response(string|\Tumutech\QrCode\Data\QrPayload $data, int $status = 200, array<string, string> $headers = [])
 * @method static \Illuminate\Http\Response png(string|\Tumutech\QrCode\Data\QrPayload $data, int $status = 200, array<string, string> $headers = [])
 * @method static \Illuminate\Http\Response svg(string|\Tumutech\QrCode\Data\QrPayload $data, int $status = 200, array<string, string> $headers = [])
 * @method static string store(string|\Tumutech\QrCode\Data\QrPayload $data, string $path, ?string $disk = null)
 * @method static QrCodeResult wifi(array<string, mixed>|\Tumutech\QrCode\Data\Wifi $wifi)
 * @method static QrCodeResult vcard(array<string, mixed>|\Tumutech\QrCode\Data\VCard $vcard)
 * @method static QrCodeResult email(string|\Tumutech\QrCode\Data\Email $email, ?string $subject = null, ?string $body = null)
 * @method static QrCodeResult sms(string|\Tumutech\QrCode\Data\Sms $number, ?string $message = null)
 *
 * @see QrCodeFactory
 */
final class QrCode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QrCodeFactory::class;
    }
}
