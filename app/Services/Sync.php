<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use League\Flysystem\Filesystem;
use RuntimeException;
use Throwable;

class Sync
{
    private Filesystem $localFilesystem;
    private Filesystem $awsFilesystem;
    private Journal $journalObject;

    public function __construct(Journal $journalObject)
    {
        $this->journalObject = $journalObject;

        $this->localFilesystem = $journalObject->getLocalFilesystem();
        $this->awsFilesystem = $journalObject->getAwsFilesystem();
    }

    public function sync($bar = null): void
    {
        [
            'synced' => $synced,
            'toUpload' => $toUpload,
            'toDownload' => $toDownload,
        ] = $this->journalObject->compare((string)config('awsync.resolve_strategy'));

        if ($bar !== null) {
            $bar->setMaxSteps($synced->count() + $toUpload->count() + $toDownload->count());
            $bar->start();
        }

        $toResult = collect();

        $synced->each(function($file) use ($bar, &$toResult) {
            $file['syncDateTime'] = Carbon::now()->toISOString();
            $file['onLocal'] = true;
            $file['onAmazon'] = true;
            $file['syncBy'] = 'DontTouch';

            $toResult->push($file);

            if ($bar !== null) {
                $bar->advance();
            }
        });

        $toDownload->each(function($file) use ($bar, &$toResult) {
            try {
                $source = $this->awsFilesystem->readStream($file['basename']);
            } catch (Throwable $throwable) {
                throw new RuntimeException($throwable->getMessage());
            }

            if (! $this->localFilesystem->putStream($this->journalObject->getFolder() . '/' . $file['basename'], $source)) {
                throw new RuntimeException(__('awsync.errors.no_file_from_aws'));
            }

            $file['synced'] = true;
            $file['onLocal'] = true;
            $file['syncBy'] = 'Download';
            $file['syncDateTime'] = Carbon::now()->toISOString();

            $toResult->push($file);

            if ($bar !== null) {
                $bar->advance();
            }
        });

        $toUpload->each(function($file) use ($bar, &$toResult) {
            try {
                $source = $this->localFilesystem->readStream($this->journalObject->getFolder() . '/' . $file['basename']);
            } catch (Throwable $throwable) {
                throw new RuntimeException($throwable->getMessage());
            }

            if (! $this->awsFilesystem->putStream($file['basename'], $source)) {
                throw new RuntimeException(__('awsync.errors.no_write_to_aws'));
            }

            $file['synced'] = true;
            $file['onAmazon'] = true;
            $file['syncBy'] = 'Upload';
            $file['syncDateTime'] = Carbon::now()->toISOString();

            $toResult->push($file);

            if ($bar !== null) {
                $bar->advance();
            }
        });

        if ($bar !== null) {
            $bar->finish();
        }

        $this->journalObject->setJournal($toResult);
    }
}
