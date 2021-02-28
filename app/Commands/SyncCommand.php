<?php
declare(strict_types=1);

namespace App\Commands;

use App\Services\Journal;
use App\Services\Sync;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class SyncCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'sync
    {folder : Folder name (the same as AWS S3 Bucket) where the files are located}
    {--baseDir= : Base working directory, "./assets" if not specified}
    {--journalDir= : Folder for journal(s), current working if not specified}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Sync files in local folder with AWS S3 Bucket';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $journalObject = new Journal(
                $this->argument('folder'),
                $this->option('baseDir'),
                $this->option('journalDir')
            );
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
            return 1;
        }

        $this->newline();

        $this->info(__('awsync.info.current_status', ['name' => $journalObject->getJournalName()]));

        $journal = $journalObject->getJournal();

        $this->table(
            [__('awsync.info.status'), __('awsync.info.quantity'), __('awsync.info.total')], [
                [ 'on Amazon', $journal->where('onAmazon', true)->count(), $journal->where('onAmazon', true)->sum('size')/1024 ],
                [ 'on Local', $journal->where('onLocal', true)->count(), $journal->where('onLocal', true)->sum('size')/1024 ],
                [ 'synced', $journal->where('synced', true)->count(), $journal->where('synced', true)->sum('size')/1024 ],
                [ 'unique basename', $journal->uniqueStrict('basename')->count(), $journal->uniqueStrict('basename')->sum('size')/1024 ],
            ]
        );

        $this->newline();
        $this->info($this->description . '...');
        $this->newline();

        try {
            (new Sync($journalObject))->sync($this->output->createProgressBar());
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
            return 1;
        }

        $this->newline(2);

        $journal = $journalObject->getJournal();

        $this->table(
            [__('awsync.info.status'), __('awsync.info.quantity'), __('awsync.info.total')], [
                [ 'on Amazon', $journal->where('onAmazon', true)->count(), $journal->where('onAmazon', true)->sum('size')/1024 ],
                [ 'on Local', $journal->where('onLocal', true)->count(), $journal->where('onLocal', true)->sum('size')/1024 ],
                [ 'synced', $journal->where('synced', true)->count(), $journal->where('synced', true)->sum('size')/1024 ],
                [ 'unique basename', $journal->uniqueStrict('basename')->count(), $journal->uniqueStrict('basename')->sum('size')/1024 ],
                [ 'Uploaded', $journal->where('syncBy', 'Upload')->count(),  $journal->where('syncBy', 'Upload')->sum('size')/1024 ],
                [ 'Downloaded', $journal->where('syncBy', 'Download')->count(), $journal->where('syncBy', 'Download')->sum('size')/1024 ],
                [ 'Do not touched', $journal->where('syncBy', 'DontTouch')->count(), $journal->where('syncBy', 'DontTouch')->sum('size')/1024 ],
            ]
        );

        $this->newLine();

        return 0;
    }
}
