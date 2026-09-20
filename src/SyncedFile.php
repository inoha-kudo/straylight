<?php

declare(strict_types=1);

namespace Straylight;

final class SyncedFile
{
    private readonly string $path;

    private readonly string $originalHash;

    private bool $closed = false;

    private function __construct(
        private readonly FileSynchronizer $synchronizer,
    ) {
        $this->path = $this->synchronizer->pull();
        $this->originalHash = $this->hash();
    }

    public static function open(FileSynchronizer $synchronizer): self
    {
        return new self($synchronizer);
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        if (! file_exists($this->path)) {
            $this->closed = true;

            throw new \RuntimeException('Temporary file no longer exists.');
        }

        if ($this->hash() !== $this->originalHash) {
            $this->synchronizer->push($this->path);
        }

        $this->closed = true;

        unlink($this->path);
    }

    public function path(): string
    {
        return $this->path;
    }

    private function hash(): string
    {
        $hash = md5_file($this->path);

        if ($hash === false) {
            throw new \RuntimeException('Could not calculate MD5 hash.');
        }

        return $hash;
    }
}
