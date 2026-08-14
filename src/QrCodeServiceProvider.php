<?php

declare(strict_types=1);

namespace Tumutech\QrCode;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Tumutech\QrCode\View\Components\QrCode as QrCodeComponent;
use Tumutech\QrCode\Writers\WriterResolver;

final class QrCodeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/qr-code.php', 'qr-code');

        $this->app->singleton(WriterResolver::class);

        $this->app->singleton(QrCodeFactory::class, function ($app): QrCodeFactory {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('qr-code', []);

            return new QrCodeFactory($config, $app->make(WriterResolver::class));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/qr-code.php' => config_path('qr-code.php'),
            ], 'qr-code-config');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'qr-code');

        Blade::component('qr-code', QrCodeComponent::class);

        Blade::directive('qrcode', function (string $expression): string {
            return "<?php echo \\Tumutech\\QrCode\\Facades\\QrCode::dataUri({$expression}); ?>";
        });
    }
}
