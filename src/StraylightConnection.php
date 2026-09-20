<?php

declare(strict_types=1);

namespace Straylight;

use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;

final class StraylightConnection extends SQLiteConnection
{
    public static function purgeAll(): void
    {
        $exception = null;

        foreach (DB::getConnections() as $name => $connection) {
            if ($connection instanceof self) {
                try {
                    DB::purge($name);
                } catch (\Throwable $e) {
                    $exception ??= $e;
                }
            }
        }

        if ($exception !== null) {
            throw $exception;
        }
    }

    #[\Override]
    public function disconnect(): void
    {
        $pdo = $this->getRawPdo();

        if ($pdo instanceof PDOStraylight) {
            $pdo->close();
        }

        parent::disconnect();
    }
}
