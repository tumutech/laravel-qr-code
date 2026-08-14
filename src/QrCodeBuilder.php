<?php

declare(strict_types=1);

namespace Tumutech\QrCode;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Tumutech\QrCode\Data\Email;
use Tumutech\QrCode\Data\QrPayload;
use Tumutech\QrCode\Data\Sms;
use Tumutech\QrCode\Data\VCard;
use Tumutech\QrCode\Data\Wifi;
use Tumutech\QrCode\Enums\ErrorCorrection;
use Tumutech\QrCode\Enums\EyeStyle;
use Tumutech\QrCode\Enums\Format;
use Tumutech\QrCode\Enums\FrameStyle;
use Tumutech\QrCode\Enums\GradientDirection;
use Tumutech\QrCode\Enums\ModuleShape;
use Tumutech\QrCode\Enums\RoundBlockSizeMode;
use Tumutech\QrCode\Exceptions\InvalidQrCodeOptionException;
use Tumutech\QrCode\Exceptions\QrCodeGenerationException;
use Tumutech\QrCode\Style\LogoComposer;
use Tumutech\QrCode\Style\ScanSafeValidator;
use Tumutech\QrCode\Style\StyledQrRenderer;
use Tumutech\QrCode\Style\StyleTemplates;
use Tumutech\QrCode\Support\ColorParser;
use Tumutech\QrCode\Writers\WriterResolver;

final class QrCodeBuilder
{
    private Format $format;

    private int $size;

    private int $margin;

    private ErrorCorrection $errorCorrection;

    private string $encoding;

    private RoundBlockSizeMode $roundBlockSizeMode;

    /** @var array{r: int, g: int, b: int, a: int} */
    private array $foreground;

    /** @var array{r: int, g: int, b: int, a: int} */
    private array $background;

    private bool $validateResult;

    private int $maxDataLength;

    private ?string $disk;

    private ?string $logoPath = null;

    private ?int $logoWidth = null;

    private bool $logoPunchoutBackground = true;

    private ?string $label = null;

    private ModuleShape $moduleShape = ModuleShape::Square;

    private EyeStyle $eyeStyle = EyeStyle::Square;

    /** @var array{start: array{r: int, g: int, b: int, a: int}, end: array{r: int, g: int, b: int, a: int}, direction: GradientDirection}|null */
    private ?array $gradient = null;

    private FrameStyle $frameStyle = FrameStyle::None;

    private ?string $frameLabel = null;

    private bool $scanSafe = false;

    private bool $scanSafeStrict = true;

    private float $moduleScale = 0.8;

    private float $roundness = 0.5;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
        private readonly WriterResolver $writers = new WriterResolver,
        private readonly StyledQrRenderer $styledRenderer = new StyledQrRenderer,
        private readonly ScanSafeValidator $scanSafeValidator = new ScanSafeValidator,
        private readonly StyleTemplates $templates = new StyleTemplates,
    ) {
        $this->format = Format::from((string) ($config['format'] ?? 'png'));
        $this->size = max(1, (int) ($config['size'] ?? 300));
        $this->margin = max(0, (int) ($config['margin'] ?? 10));
        $this->errorCorrection = ErrorCorrection::from((string) ($config['error_correction'] ?? 'medium'));
        $this->encoding = (string) ($config['encoding'] ?? 'UTF-8');
        $this->roundBlockSizeMode = RoundBlockSizeMode::from((string) ($config['round_block_size_mode'] ?? 'margin'));
        $this->foreground = $this->normalizeColor($config['foreground'] ?? ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $this->background = $this->normalizeColor($config['background'] ?? ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        $this->validateResult = (bool) ($config['validate_result'] ?? false);
        $this->maxDataLength = max(1, (int) ($config['max_data_length'] ?? 4296));
        $this->disk = isset($config['disk']) ? (string) $config['disk'] : null;
    }

    public function format(Format|string $format): self
    {
        $clone = clone $this;
        $clone->format = $format instanceof Format ? $format : Format::from(strtolower($format));

        return $clone;
    }

    public function size(int $size): self
    {
        if ($size < 1) {
            throw new InvalidQrCodeOptionException('QR code size must be at least 1.');
        }

        $clone = clone $this;
        $clone->size = $size;

        return $clone;
    }

    public function margin(int $margin): self
    {
        if ($margin < 0) {
            throw new InvalidQrCodeOptionException('QR code margin cannot be negative.');
        }

        $clone = clone $this;
        $clone->margin = $margin;

        return $clone;
    }

    public function errorCorrection(ErrorCorrection|string $level): self
    {
        $clone = clone $this;
        $clone->errorCorrection = $level instanceof ErrorCorrection
            ? $level
            : ErrorCorrection::from(strtolower($level));

        return $clone;
    }

    public function encoding(string $encoding): self
    {
        if ($encoding === '') {
            throw new InvalidQrCodeOptionException('Encoding cannot be empty.');
        }

        $clone = clone $this;
        $clone->encoding = $encoding;

        return $clone;
    }

    public function roundBlockSizeMode(RoundBlockSizeMode|string $mode): self
    {
        $clone = clone $this;
        $clone->roundBlockSizeMode = $mode instanceof RoundBlockSizeMode
            ? $mode
            : RoundBlockSizeMode::from(strtolower($mode));

        return $clone;
    }

    public function color(int|string $r, ?int $g = null, ?int $b = null, int $a = 0): self
    {
        $clone = clone $this;
        $clone->foreground = is_string($r)
            ? ColorParser::parse($r, $a)
            : $this->normalizeColor(['r' => $r, 'g' => $g ?? 0, 'b' => $b ?? 0, 'a' => $a]);

        return $clone;
    }

    public function backgroundColor(int|string $r, ?int $g = null, ?int $b = null, int $a = 0): self
    {
        $clone = clone $this;
        $clone->background = is_string($r)
            ? ColorParser::parse($r, $a)
            : $this->normalizeColor(['r' => $r, 'g' => $g ?? 0, 'b' => $b ?? 0, 'a' => $a]);

        return $clone;
    }

    public function module(ModuleShape|string $shape, ?float $scale = null): self
    {
        $clone = clone $this;
        $clone->moduleShape = $shape instanceof ModuleShape ? $shape : ModuleShape::from(strtolower($shape));

        if ($scale !== null) {
            $clone->moduleScale = $this->assertUnitInterval($scale, 'module scale');
        }

        return $clone;
    }

    public function moduleScale(float $scale): self
    {
        $clone = clone $this;
        $clone->moduleScale = $this->assertUnitInterval($scale, 'module scale');

        return $clone;
    }

    public function roundness(float $intensity): self
    {
        $clone = clone $this;
        $clone->roundness = $this->assertUnitInterval($intensity, 'roundness');

        return $clone;
    }

    public function eye(EyeStyle|string $style): self
    {
        $clone = clone $this;
        $clone->eyeStyle = $style instanceof EyeStyle ? $style : EyeStyle::from(strtolower($style));

        return $clone;
    }

    public function gradient(
        string $start,
        string $end,
        GradientDirection|string $direction = GradientDirection::Diagonal,
    ): self {
        $clone = clone $this;
        $clone->gradient = [
            'start' => ColorParser::parse($start),
            'end' => ColorParser::parse($end),
            'direction' => $direction instanceof GradientDirection
                ? $direction
                : GradientDirection::from(strtolower($direction)),
        ];
        $clone->foreground = $clone->gradient['start'];

        return $clone;
    }

    public function frame(FrameStyle|string $style = FrameStyle::Badge, ?string $label = 'SCAN ME'): self
    {
        $clone = clone $this;
        $clone->frameStyle = $style instanceof FrameStyle ? $style : FrameStyle::from(strtolower($style));
        $clone->frameLabel = $label;

        return $clone;
    }

    public function template(string $name): self
    {
        $preset = $this->templates->get($name);
        $clone = clone $this;
        $clone->moduleShape = $preset['module'];
        $clone->eyeStyle = $preset['eye'];
        $clone->foreground = $preset['foreground'];
        $clone->background = $preset['background'];
        $clone->frameStyle = $preset['frame'];
        $clone->frameLabel = $preset['frame_label'] ?? $clone->frameLabel;
        $clone->errorCorrection = $preset['error_correction'];
        $clone->gradient = $preset['gradient'] ?? null;
        $clone->moduleScale = (float) ($preset['module_scale'] ?? $clone->moduleScale);
        $clone->roundness = (float) ($preset['roundness'] ?? $clone->roundness);

        return $clone;
    }

    public function scanSafe(bool $enabled = true, bool $strict = true): self
    {
        $clone = clone $this;
        $clone->scanSafe = $enabled;
        $clone->scanSafeStrict = $strict;

        return $clone;
    }

    /**
     * @return list<string>
     */
    public function scanSafeIssues(): array
    {
        return $this->scanSafeValidator->issues(
            foreground: $this->foreground,
            background: $this->background,
            gradientEnd: $this->gradient['end'] ?? null,
            module: $this->moduleShape,
            eye: $this->eyeStyle,
            errorCorrection: $this->errorCorrection,
            size: $this->size,
            moduleMargin: max(4, (int) round($this->margin / 4)),
            logoPath: $this->logoPath,
            logoWidth: $this->logoWidth,
            frame: $this->frameStyle,
        );
    }

    /**
     * Place a brand mark in the center of the QR code.
     *
     * When $width is null, the logo is ~22% of the QR size. A rounded
     * background punchout is drawn behind it by default so modules do not
     * show through the logo. Error correction is raised to high automatically.
     */
    public function logo(string $path, ?int $width = null, bool $punchoutBackground = true): self
    {
        if ($path === '' || ! is_file($path)) {
            throw new InvalidQrCodeOptionException("Logo file not found [{$path}].");
        }

        $clone = clone $this;
        $clone->logoPath = $path;
        $clone->logoWidth = $width;
        $clone->logoPunchoutBackground = $punchoutBackground;

        if ($clone->errorCorrection !== ErrorCorrection::High) {
            $clone->errorCorrection = ErrorCorrection::High;
        }

        return $clone;
    }

    public function label(string $text): self
    {
        $clone = clone $this;
        $clone->label = $text;

        return $clone;
    }

    public function disk(?string $disk): self
    {
        $clone = clone $this;
        $clone->disk = $disk;

        return $clone;
    }

    public function validateResult(bool $validate = true): self
    {
        $clone = clone $this;
        $clone->validateResult = $validate;

        return $clone;
    }

    public function generate(string|QrPayload $data): QrCodeResult
    {
        $payload = $data instanceof QrPayload ? $data->toString() : $data;
        $this->assertData($payload);

        if ($this->scanSafe) {
            $issues = $this->scanSafeIssues();
            if ($this->scanSafeStrict) {
                $this->scanSafeValidator->assertSafe($issues);
            }
        }

        if ($this->usesStyledRenderer()) {
            try {
                return $this->styledRenderer->render(
                    data: $payload,
                    format: $this->format,
                    size: $this->size,
                    pixelMargin: $this->margin,
                    errorCorrection: $this->errorCorrection,
                    encoding: $this->encoding,
                    module: $this->moduleShape,
                    eye: $this->eyeStyle,
                    foreground: $this->foreground,
                    background: $this->background,
                    gradient: $this->gradient,
                    frame: $this->frameStyle,
                    frameLabel: $this->frameLabel,
                    logoPath: $this->logoPath,
                    logoWidth: $this->logoWidth,
                    logoPunchout: $this->logoPunchoutBackground,
                    moduleScale: $this->moduleScale,
                    roundness: $this->roundness,
                );
            } catch (InvalidQrCodeOptionException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                throw new QrCodeGenerationException($exception->getMessage(), previous: $exception);
            }
        }

        try {
            $builder = Builder::create()
                ->writer($this->writers->resolve($this->format))
                ->data($payload)
                ->encoding(new Encoding($this->encoding))
                ->errorCorrectionLevel($this->errorCorrection->toEndroid())
                ->size($this->size)
                ->margin($this->margin)
                ->roundBlockSizeMode($this->roundBlockSizeMode->toEndroid())
                ->foregroundColor(new Color(
                    $this->foreground['r'],
                    $this->foreground['g'],
                    $this->foreground['b'],
                    $this->foreground['a'],
                ))
                ->backgroundColor(new Color(
                    $this->background['r'],
                    $this->background['g'],
                    $this->background['b'],
                    $this->background['a'],
                ))
                ->validateResult($this->validateResult);

            if ($this->logoPath !== null) {
                $logoWidth = (new LogoComposer)->resolveWidth($this->size, $this->logoWidth);

                $builder = $builder
                    ->logoPath($this->logoPath)
                    ->logoPunchoutBackground($this->logoPunchoutBackground)
                    ->logoResizeToWidth($logoWidth);
            }

            if ($this->label !== null && $this->label !== '') {
                $builder = $builder->labelText($this->label);
            }

            /** @var ResultInterface $result */
            $result = $builder->build();
        } catch (InvalidQrCodeOptionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new QrCodeGenerationException($exception->getMessage(), previous: $exception);
        }

        return new QrCodeResult(
            contents: $result->getString(),
            mimeType: $result->getMimeType(),
            format: $this->format,
            dataUri: $result->getDataUri(),
        );
    }

    private function usesStyledRenderer(): bool
    {
        return $this->moduleShape !== ModuleShape::Square
            || $this->eyeStyle !== EyeStyle::Square
            || $this->gradient !== null
            || $this->frameStyle !== FrameStyle::None;
    }

    public function dataUri(string|QrPayload $data): string
    {
        return $this->generate($data)->getDataUri();
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function response(string|QrPayload $data, int $status = 200, array $headers = []): Response
    {
        $result = $this->generate($data);

        return new Response($result->getString(), $status, array_merge([
            'Content-Type' => $result->getMimeType(),
        ], $headers));
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function png(string|QrPayload $data, int $status = 200, array $headers = []): Response
    {
        return $this->format(Format::Png)->response($data, $status, $headers);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function svg(string|QrPayload $data, int $status = 200, array $headers = []): Response
    {
        return $this->format(Format::Svg)->response($data, $status, $headers);
    }

    public function store(string|QrPayload $data, string $path, ?string $disk = null): string
    {
        $result = $this->generate($data);
        $diskName = $disk ?? $this->disk ?? 'local';

        $this->filesystem($diskName)->put($path, $result->getString());

        return $path;
    }

    /**
     * @param  array{
     *     ssid: string,
     *     password?: string|null,
     *     encryption?: string,
     *     hidden?: bool
     * }|Wifi  $wifi
     */
    public function wifi(array|Wifi $wifi): QrCodeResult
    {
        $payload = $wifi instanceof Wifi
            ? $wifi
            : Wifi::make(
                ssid: $wifi['ssid'],
                password: $wifi['password'] ?? null,
                encryption: $wifi['encryption'] ?? 'WPA',
                hidden: $wifi['hidden'] ?? false,
            );

        return $this->generate($payload);
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
     * }|VCard  $vcard
     */
    public function vcard(array|VCard $vcard): QrCodeResult
    {
        return $this->generate($vcard instanceof VCard ? $vcard : VCard::make($vcard));
    }

    public function email(string|Email $email, ?string $subject = null, ?string $body = null): QrCodeResult
    {
        $payload = $email instanceof Email
            ? $email
            : Email::make($email, $subject, $body);

        return $this->generate($payload);
    }

    public function sms(string|Sms $number, ?string $message = null): QrCodeResult
    {
        $payload = $number instanceof Sms
            ? $number
            : Sms::make($number, $message);

        return $this->generate($payload);
    }

    private function assertData(string $data): void
    {
        if ($data === '') {
            throw new InvalidQrCodeOptionException('QR code data cannot be empty.');
        }

        if (strlen($data) > $this->maxDataLength) {
            throw new InvalidQrCodeOptionException(
                "QR code data exceeds the maximum length of {$this->maxDataLength} bytes.",
            );
        }
    }

    /**
     * @param  array<string, mixed>  $color
     * @return array{r: int, g: int, b: int, a: int}
     */
    private function normalizeColor(array $color): array
    {
        $normalized = [
            'r' => (int) ($color['r'] ?? 0),
            'g' => (int) ($color['g'] ?? 0),
            'b' => (int) ($color['b'] ?? 0),
            'a' => (int) ($color['a'] ?? 0),
        ];

        foreach (['r', 'g', 'b'] as $channel) {
            if ($normalized[$channel] < 0 || $normalized[$channel] > 255) {
                throw new InvalidQrCodeOptionException("Color channel [{$channel}] must be between 0 and 255.");
            }
        }

        if ($normalized['a'] < 0 || $normalized['a'] > 127) {
            throw new InvalidQrCodeOptionException('Color alpha must be between 0 and 127.');
        }

        return $normalized;
    }

    private function assertUnitInterval(float $value, string $label): float
    {
        if ($value <= 0 || $value > 1) {
            throw new InvalidQrCodeOptionException("The {$label} must be between 0 (exclusive) and 1 (inclusive).");
        }

        return $value;
    }

    private function filesystem(string $disk): Filesystem
    {
        return Storage::disk($disk);
    }
}
