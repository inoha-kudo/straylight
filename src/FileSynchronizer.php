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
        $tmpFile = tempnam(sys_get_temp_dir(), 'database.sqlite');

        if ($tmpFile === false) {
            throw new \RuntimeException('Could not create temporary file.');
        }

        $resource = $this->disk->readStream($this->path);

        if (! is_resource($resource) && ! $this->disk->exists($this->path)) {
            return $tmpFile;
        }

        if (! is_resource($resource) || file_put_contents($tmpFile, $resource) === false) {
            unlink($tmpFile);

            throw new \RuntimeException('Could not pull disk file to temporary file.');
        }

        fclose($resource);

        return $tmpFile;
    }

    public function push(string $tmpFile): void
    {
        $resource = fopen($tmpFile, 'rb');

        assert(is_resource($resource));

        $this->disk->writeStream($this->path, $resource);

        fclose($resource);
    }
}
