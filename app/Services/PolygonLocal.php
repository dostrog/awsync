<?php
declare(strict_types=1);

namespace App\Services;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

class PolygonLocal extends Polygon
{
    protected string $baseDir;
    protected Filesystem $filesystem;

    public function __construct(string $folder, int $quantity, string $baseDir = null)
    {
        parent::__construct($folder ?? '', $quantity);

        $this->baseDir = $baseDir ?? getcwd() . config('awsync.default_base_dir');
        $this->filesystem = $this->prepareFileSystem();
    }

    public function getFileSystem(): Filesystem
    {
        return $this->filesystem;
    }

    protected function prepareFileSystem(): Filesystem
    {
        return new Filesystem(new Local($this->baseDir));
    }

    public function clean(bool $forceDelete = false): self
    {
        $contents = $this->filesystem->listContents($this->folder, true);
        collect($contents)->map(fn($file) => $this->filesystem->delete($file['path']))->all();

        if ($forceDelete) {
            $this->filesystem->deleteDir($this->folder);
        }

        return $this;
    }

    public function populate(string $prefix = '', $bar = null): void
    {
        $this->putFiles($this->filesystem, $this->nums, $prefix, $bar);
    }
}
