<?php

declare(strict_types=1);

namespace Straylight;

use Illuminate\Contracts\Filesystem\Filesystem;

final readonly class FileSynchronizer
{
    public function __construct(
        private Filesystem $disk,
        private string $path,
    ) {}

    public function pull(): string
    {
        $resource = $this->disk->readStream($this->path);

        if (! is_resource($resource) && $this->disk->exists($this->path)) {
            throw new \RuntimeException('Could not read disk file.');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'database.sqlite');

        if ($tmpFile === false) {
            throw new \RuntimeException('Could not create temporary file.');
        }

        if (is_resource($resource)) {
            $this->copy($resource, $tmpFile);
        }

        return $tmpFile;
    }

    public function push(string $tmpFile): void
    {
        $resource = fopen($tmpFile, 'rb');

        if ($resource === false) {
            throw new \RuntimeException('Could not open temporary file.');
        }

        try {
            if ($this->disk->writeStream($this->path, $resource) === false) {
                throw new \RuntimeException('Could not push temporary file to disk file.');
            }
        } finally {
            fclose($resource);
        }
    }

    /**
     * @param  resource  $resource
     */
    private function copy($resource, string $tmpFile): void
    {
        try {
            if (file_put_contents($tmpFile, $resource) === false) {
                throw new \RuntimeException('Could not pull disk file to temporary file.');
            }
        } catch (\Throwable $e) {
            unlink($tmpFile);

            throw $e;
        } finally {
            fclose($resource);
        }
    }
}
