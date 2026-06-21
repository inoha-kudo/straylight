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

        Config::set([
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
                __DIR__.'/../config/straylight.php' => $this->app->configPath('straylight.php'),
            ], 'straylight-config');
        }

        DB::extend('straylight', function (array $config, string $name) {
            [$database, $prefix] = [
                $config['database'] ?? null, $config['prefix'] ?? null,
            ];

            assert(is_string($database));
            assert(is_string($prefix));

            $connection = new StraylightConnection(
                fn () => new StraylightConnector()->connect($config),
                $database,
                $prefix,
                ['driver' => 'sqlite', 'name' => $name] + $config,
            );

            $lock = $config['lock'] ?? [];

            assert(is_array($lock));

            if (isset($lock['store'])) {
                $connection->setReadPdo(fn () => new StraylightConnector()->connectReadOnly($config));
            }

            return $connection;
        });

        $this->app->terminating(function () {
            StraylightConnection::purgeAll();
        });
    }
}
