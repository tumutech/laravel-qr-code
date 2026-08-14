<?php

declare(strict_types=1);

namespace Tumutech\QrCode\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Tumutech\QrCode\Facades\QrCode as QrCodeFacade;

final class QrCode extends Component
{
    public function __construct(
        public readonly string $data,
        public readonly int $size = 300,
        public readonly string $format = 'png',
        public readonly string $alt = 'QR code',
        public readonly ?string $class = null,
        public readonly ?string $logo = null,
        public readonly ?int $logoWidth = null,
        public readonly ?string $template = null,
        public readonly bool $scanSafe = false,
    ) {}

    public function render(): View
    {
        $builder = QrCodeFacade::format($this->format)->size($this->size);

        if ($this->template !== null && $this->template !== '') {
            $builder = $builder->template($this->template);
        }

        if ($this->logo !== null && $this->logo !== '') {
            $builder = $builder->logo($this->logo, $this->logoWidth);
        }

        if ($this->scanSafe || $this->logo !== null) {
            $builder = $builder->scanSafe();
        }

        return view('qr-code::components.qr-code', [
            'dataUri' => $builder->dataUri($this->data),
            'alt' => $this->alt,
            'class' => $this->class,
            'size' => $this->size,
        ]);
    }
}
