<?php

declare(strict_types=1);

namespace Straylight;

use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Support\Facades\Storage;

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
            new SyncedFile(new FileSynchronizer(Storage::disk($disk), $path)),
            $options,
        );
    }
}
