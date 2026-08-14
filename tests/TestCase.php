<?php

declare(strict_types=1);

namespace Tumutech\QrCode\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tumutech\QrCode\Facades\QrCode;
use Tumutech\QrCode\QrCodeServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            QrCodeServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'QrCode' => QrCode::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('qr-code.format', 'png');
        $app['config']->set('qr-code.size', 200);
        $app['config']->set('qr-code.margin', 4);
        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => storage_path('app'),
        ]);
    }
}
