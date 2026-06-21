<?php

declare(strict_types=1);

namespace Straylight;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

final class StraylightServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/straylight.php',
            'straylight',
        );

        config([
            'database.connections.straylight' => [
                ...Config::array('straylight.connection'),
                'url' => null,
                'database' => ':memory:',
                'journal_mode' => 'DELETE',
            ],
        ]);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/straylight.php' => config_path('straylight.php'),
            ], 'straylight-config');
        }

        DB::extend('straylight', function (array $config, string $name) {
            [$database, $prefix] = [
                $config['database'] ?? null, $config['prefix'] ?? null,
            ];

            assert(is_string($database));
            assert(is_string($prefix));

            return new StraylightConnection(
                fn () => new StraylightConnector()->connect($config),
                $database,
                $prefix,
                ['driver' => 'sqlite', 'name' => $name] + $config,
            );
        });

        $this->app->terminating(function () {
            StraylightConnection::purgeAll();
        });
    }
}
