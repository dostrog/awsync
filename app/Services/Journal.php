<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use RuntimeException;
use Throwable;

class Journal
{
    private string $folder;
    private Filesystem $localFilesystem;
    private Filesystem $journalFilesystem;
    private Filesystem $awsFilesystem;
    private string $journalName;
    private Collection $journal;
    private string $bucket;

    use AWS;

    public function __construct(string $folder, $baseDir = null, $store = null)
    {
        $this->folder = ($folder !== '') ? $folder : 'bucket';
        $this->bucket = $this->folder;
        $baseDir = $baseDir ?? getcwd() . config('awsync.default_base_dir');
        $store = $store ?? getcwd() . config('awsync.default_journal_dir');
        $this->journalName = $this->prepareJournalName();

        $this->journalFilesystem = $this->prepareLocalFileSystem($store);
        $this->localFilesystem = $this->prepareLocalFileSystem($baseDir);
        $this->awsFilesystem = $this->prepareAwsFileSystem();

        $this->setJournal($this->createRawJournal());
    }

    public function getJournal(): Collection
    {
        return $this->journal;
    }

    private function collectFiles(Filesystem $filesystem, string $folder = '', $local = true): Collection
    {
        try {
            return collect($filesystem->listContents($folder, true))
                ->transform(fn($item, $key) => array_merge(
                    Arr::only($item, ['basename', 'timestamp', 'size']),
                    [
                        'synced' => false,
                        'syncDateTime' => '',
                        'syncBy' => '',
                        'onLocal' => $local,
                        'onAmazon' => !$local,
                    ])
                );
        } catch (Throwable $th) {
            throw new RuntimeException($th->getMessage());
        }
    }

    public function writeJournal(Collection $journal): void
    {
        try {
            $this->journalFilesystem->put(
                $this->journalName,
                $journal->toJson(JSON_PRETTY_PRINT)
            );
        } catch (Throwable $throwable) {
            throw new RuntimeException($throwable->getMessage());
        }
    }

    public function createRawJournal(): Collection
    {
        return $this->collectFiles($this->localFilesystem, $this->folder)
            ->concat($this->collectFiles($this->awsFilesystem, '', false));
    }

    protected function prepareAwsFilesystem(): Filesystem
    {
        try {
            $this->s3client = $this->createS3Client();
            return new Filesystem(new AwsS3Adapter($this->s3client, $this->bucket));
        } catch (Throwable $th) {
            throw new RuntimeException($th->getMessage());
        }
    }

    protected function prepareLocalFileSystem(string $root): Filesystem
    {
        return new Filesystem(new Local($root));
    }

    private function prepareJournalName(string $prefix = 'journal', string $ext = 'json'): string
    {
        return sprintf("%s-%s.%s", $prefix, $this->folder, $ext);
    }

    public function getJournalName(): string
    {
        return $this->journalName;
    }

    public function compare(string $strategy): array
    {
        $onLocal = $this->journal
            ->where('onAmazon', false)
            ->where('onLocal', true);

        $onAmazon = $this->journal->diffKeys($onLocal);

        $toUpload = $onLocal->reject(fn($localFile) => $onAmazon->contains(fn($awsFile) => $awsFile['basename'] === $localFile['basename']));
        $toDownload = $onAmazon->reject(fn($awsFile) => $onLocal->contains(fn($localFile) => $localFile['basename'] === $awsFile['basename']));

        $toResolve = $this->journal
            ->except(array_merge($toUpload->keys()->toArray(), $toDownload->keys()->toArray()));

        $toResolveLocal = $toResolve
            ->where('onAmazon', false)
            ->where('onLocal', true);

        $toResolveAmazon = $toResolve->diffKeys($toResolveLocal);

        $toResolveLocal->transform(function($file) use ($strategy, &$toResolveAmazon){
            $theCommonAmazonItem = $toResolveAmazon
                ->where('basename', $file['basename'])
                ->where('size', $file['size']);

            $theNamedAmazonItem = $toResolveAmazon
                ->where('basename', $file['basename'])
                ->whereNotIn('size', $file['size']);

            if($theCommonAmazonItem->isNotEmpty()) {
                $toResolveAmazon = $toResolveAmazon->except($theCommonAmazonItem->keys());
                $file['synced'] = true;
            }

            if($theNamedAmazonItem->isNotEmpty()) {
                switch ($strategy) {
                    case 'LocalPriority':
                    default:
                        $file['onAmazon'] = false;
                        $file['onLocal'] = true;
                        break;
                    case 'AmazonPriority':
                        $file['onAmazon'] = true;
                        $file['onLocal'] = false;
                        break;
                    case 'BiggerPriority':
                        if((int)$theNamedAmazonItem->values()->toArray()[0]['size'] > (int)$file['size']) {
                            $file['onAmazon'] = true;
                            $file['onLocal'] = false;
                        } else {
                            $file['onAmazon'] = false;
                            $file['onLocal'] = true;
                        }
                        break;
                    case 'SmallerPriority':
                        if((int)$theNamedAmazonItem->values()->toArray()[0]['size'] < (int)$file['size']) {
                            $file['onAmazon'] = true;
                            $file['onLocal'] = false;
                        } else {
                            $file['onAmazon'] = false;
                            $file['onLocal'] = true;
                        }
                        break;
                }
            }

            return $file;
        });

        $toUploadAfterResolve = $toResolveLocal->reject(fn($file) => $file['synced'] || ($file['onAmazon'] && !$file['onLocal']));
        $toDownloadAfterResolve = $toResolveLocal->diffKeys($toUploadAfterResolve)->where('synced', false);

        return [
            'synced' => $toResolveLocal->where('synced', true),
            'toUpload' => $toUpload->concat($toUploadAfterResolve),
            'toDownload' => $toDownload->concat($toDownloadAfterResolve),
        ];
    }

    /**
     * @return Filesystem
     */
    public function getLocalFilesystem(): Filesystem
    {
        return $this->localFilesystem;
    }

    /**
     * @return Filesystem
     */
    public function getAwsFilesystem(): Filesystem
    {
        return $this->awsFilesystem;
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * @param Collection $journal
     */
    public function setJournal(Collection $journal): void
    {
        $this->journal = $journal;
        $this->writeJournal($journal);
    }
}
