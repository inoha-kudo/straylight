<?php

declare(strict_types=1);

namespace Straylight\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

final class StraylightService
{
    private const array CONNECTION_NAMES = ['straylight', 'straylight::read', 'straylight::write', 'straylight::direct'];

    public function using(string $path, \Closure $callback, ?string $disk = null, ?string $lockStore = null): mixed
    {
        if ($this->hasActiveTransaction()) {
            throw new \LogicException('Cannot call using() during an active Straylight transaction.');
        }

        $overrides = [
            'database.default' => 'straylight',
            'database.connections.straylight.path' => $path,
            'database.connections.straylight.disk' => $disk,
            'database.connections.straylight.lock.store' => $lockStore,
        ];
        $original = Config::getMany(array_keys($overrides));

        try {
            Config::set(Arr::whereNotNull($overrides));

            $this->purgeAll();

            return $callback();
        } finally {
            Config::set($original);

            $this->purgeAll();
        }
    }

    private function hasActiveTransaction(): bool
    {
        return new Collection(DB::getConnections())
            ->only(self::CONNECTION_NAMES)
            ->contains(fn (Connection $connection) => $connection->transactionLevel() > 0);
    }

    private function purgeAll(): void
    {
        $exception = null;

        foreach (self::CONNECTION_NAMES as $name) {
            try {
                DB::purge($name);
            } catch (\Throwable $e) {
                $exception ??= $e;
            }
        }

        if ($exception !== null) {
            throw $exception;
        }
    }
}
