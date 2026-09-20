<?php

declare(strict_types=1);

namespace Straylight;

use Pdo\Sqlite;

final class PDOStraylight extends Sqlite
{
    /**
     * @param  array<int, mixed>  $options
     */
    public function __construct(
        private readonly SyncedFile $file,
        array $options = [],
    ) {
        try {
            parent::__construct('sqlite:'.$this->file->path(), options: $options);
        } catch (\Throwable $e) {
            $this->file->close();

            throw $e;
        }
    }

    public function __destruct()
    {
        try {
            $this->close();
        } catch (\Throwable) {
            try {
                $this->file->discard();
            } catch (\Throwable) {
                return;
            }
        }
    }

    public function close(): void
    {
        if ($this->inTransaction()) {
            $this->rollBack();
        }

        $this->file->close();
    }
}
