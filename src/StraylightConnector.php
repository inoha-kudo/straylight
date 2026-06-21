<?php

declare(strict_types=1);

namespace Straylight;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Pdo\Sqlite;

final class StraylightConnector extends SQLiteConnector
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, mixed>  $options
     */
    #[\Override]
    public function createConnection($dsn, array $config, array $options): \PDO
    {
        [$disk, $path] = [
            $config['disk'] ?? null, $config['path'] ?? null,
        ];

        assert(is_string($disk));
        assert(is_string($path));

        return new PDOStraylight(
            SyncedFile::open(
                new FileSynchronizer(Storage::disk($disk), $path),
                $this->createLock($config, 'straylight:'.sha1($disk.':'.$path)),
                ($options[Sqlite::ATTR_OPEN_FLAGS] ?? null) === Sqlite::OPEN_READONLY,
            ),
            $options,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function connectReadOnly(array $config): \PDO
    {
        return $this->connect(array_replace_recursive($config, [
            'lock' => ['store' => null],
            'options' => [Sqlite::ATTR_OPEN_FLAGS => Sqlite::OPEN_READONLY],
        ]));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createLock(array $config, string $name): ?FileLock
    {
        $lock = $config['lock'] ?? [];

        assert(is_array($lock));

        if (is_null($store = $lock['store'] ?? null)) {
            return null;
        }

        [$seconds, $waitSeconds] = [
            $lock['seconds'] ?? 0, $lock['wait_seconds'] ?? 10,
        ];

        assert(is_string($store));
        assert(is_int($seconds));
        assert(is_int($waitSeconds));

        $provider = Cache::store($store)->getStore();

        assert($provider instanceof LockProvider);

        return new FileLock($provider->lock($name, $seconds), $waitSeconds);
    }
}
