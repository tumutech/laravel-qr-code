<?php

declare(strict_types=1);

namespace Tumutech\QrCode;

use Tumutech\QrCode\Writers\WriterResolver;

final class QrCodeFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
        private readonly WriterResolver $writers = new WriterResolver,
    ) {}

    public function builder(): QrCodeBuilder
    {
        return new QrCodeBuilder($this->config, $this->writers);
    }

    /**
     * @param  list<mixed>  $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->builder()->{$method}(...$arguments);
    }
}
