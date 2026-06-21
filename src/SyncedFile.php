<?php

declare(strict_types=1);

namespace Straylight;

final class SyncedFile
{
    private ?string $tmpFile = null;

    private ?string $originalHash = null;

    public function __construct(
        private readonly FileSynchronizer $synchronizer,
    ) {}

    public function open(): string
    {
        $this->tmpFile = $this->synchronizer->pull();
        $this->originalHash = $this->hash($this->tmpFile);

        return $this->tmpFile;
    }

    public function close(): void
    {
        if ($this->tmpFile === null || ! file_exists($this->tmpFile)) {
            return;
        }

        try {
            if ($this->hash($this->tmpFile) !== $this->originalHash) {
                $this->synchronizer->push($this->tmpFile);
            }
        } finally {
            unlink($this->tmpFile);
        }
    }

    private function hash(string $filename): string
    {
        $hash = md5_file($filename);

        if ($hash === false) {
            throw new \RuntimeException('Could not calculate MD5 hash.');
        }

        return $hash;
    }
}
