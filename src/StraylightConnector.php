<?php

declare(strict_types=1);

namespace Straylight;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Pdo\Sqlite;

final class StraylightConnector extends SQLiteConnector
{
    /**
     * @param  array<mixed>  $config
     * @param  array<mixed>  $options
     */
    #[\Override]
    public function createConnection($dsn, array $config, array $options): \PDO
    {
        return new PDOStraylight(
            $this->openSyncedFile($config, ($options[Sqlite::ATTR_OPEN_FLAGS] ?? null) === Sqlite::OPEN_READONLY),
            $options,
        );
    }

    /**
     * @param  array<mixed>  $config
     */
    public function connectReadOnly(array $config): \PDO
    {
        return $this->connect(array_replace_recursive($config, [
            'lock' => ['store' => null],
            'options' => [Sqlite::ATTR_OPEN_FLAGS => Sqlite::OPEN_READONLY],
        ]));
    }

    /**
     * @param  array<mixed>  $config
     */
    private function openSyncedFile(array $config, bool $readOnly): SyncedFile
    {
        [$disk, $path] = [
            Arr::string($config, 'disk'), Arr::string($config, 'path'),
        ];

        return SyncedFile::open(
            new FileSynchronizer(Storage::disk($disk), $path),
            $this->createLock($config, 'straylight:'.sha1($disk.':'.$path)),
            $readOnly,
        );
    }

    /**
     * @param  array<mixed>  $config
     */
    private function createLock(array $config, string $name): ?FileLock
    {
        if (Arr::get($config, 'lock.store') === null) {
            return null;
        }

        $provider = Cache::store(Arr::string($config, 'lock.store'))->getStore();

        assert($provider instanceof LockProvider);

        return new FileLock(
            $provider->lock($name, Arr::integer($config, 'lock.seconds', 0)),
            Arr::integer($config, 'lock.wait_seconds', 10),
        );
    }
}
