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
        $tmpFile = $this->file->open();

        parent::__construct('sqlite:'.$tmpFile, options: $options);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        if ($this->inTransaction()) {
            $this->rollBack();
        }

        $this->file->close();
    }
}
