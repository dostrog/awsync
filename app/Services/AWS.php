<?php
declare(strict_types=1);

namespace App\Services;

use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;

trait AWS
{
    private S3ClientInterface $s3client;

    protected function createS3Client(): S3ClientInterface
    {
        return new S3Client([
            'endpoint' => config('awsync.aws.endpoint'),
            'credentials' => [
                'key'    => config('awsync.aws.access_key_id'),
                'secret' => config('awsync.aws.secret_access_key'),
            ],
            'region' => config('awsync.aws.region'),
            'version' => config('awsync.aws.version'),
        ]);
    }

    protected function deleteBucket(string $bucket)
    {
        $this->s3client->deleteBucket(['Bucket' => $bucket]);
    }

    protected function createBucket(string $bucket)
    {
        $this->s3client->createBucket(['Bucket' => $bucket]);
    }
}
