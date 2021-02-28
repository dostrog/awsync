<?php
declare(strict_types=1);

namespace App\Services;

use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use RuntimeException;
use Throwable;

class PolygonAWS extends Polygon
{
    private Filesystem $filesystem;
    private string $bucket;

    use AWS;

    public function __construct(string $bucket, int $quantity, $folder = null)
    {
        parent::__construct($folder ?? '', $quantity);

        $this->bucket = $bucket;
        $this->filesystem = $this->prepareFileSystem();
    }

    public function getFileSystem(): Filesystem
    {
        return $this->filesystem;
    }

    protected function prepareFileSystem(): Filesystem
    {
        try {
            $this->s3client = $this->createS3Client();
            return new Filesystem(new AwsS3Adapter($this->s3client, $this->bucket));
        } catch (Throwable $th) {
            throw new RuntimeException($th->getMessage());
        }
    }

    public function clean(bool $forceDelete = false): self
    {
        if (! $this->s3client->doesBucketExist($this->bucket)) {
            return $this;
        }

        $contents = $this->filesystem->listContents('', true);
        collect($contents)->map(fn($file) => $this->filesystem->delete($file['path']))->all();

        if ($forceDelete) {
            $this->deleteBucket($this->bucket);
        }

        return $this;
    }

    public function populate(string $prefix = '', $bar = null): void
    {
        if (! $this->s3client->doesBucketExist($this->bucket)) {
            $this->createBucket($this->bucket);
        }

        $this->putFiles($this->filesystem, $this->nums, $prefix, $bar);
    }
}
