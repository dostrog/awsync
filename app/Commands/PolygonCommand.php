<?php
declare(strict_types=1);

namespace App\Commands;

use App\Services\PolygonCommon;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class PolygonCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'polygon
    {folder : Folder name where files will be stored (folder === bucket)}
    {max_num=20 : Max number of files for each filesystem}
    {max_common=20 : Max number identical files in both filesystem}
    {max_named=20 : Max number files with the same name but size in both filesystem}
    {--baseDir= : Base working directory, "./assets" if not specified}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Populate test files with random data on local and AWS S3 filesystem';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->newline();
        $this->info($this->description . '...');
        $this->newline();

        $this->call('polygon:local', [
            'folder' => $this->argument('folder'),
            'max_num' => $this->argument('max_num'),
            '--baseDir' => $this->option('baseDir')
        ]);

        $this->call('polygon:aws', [
            'bucket' => $this->argument('folder'),
            'max_num' => $this->argument('max_num'),
            '--folder' => ''
        ]);

        try {
            $bar = $this->output->createProgressBar();

            $polygon = new PolygonCommon(
                $this->argument('folder'),
                (int)$this->argument('max_common'),
                (int)$this->argument('max_named'),
                $this->option('baseDir')
            );

            $this->newline();
            $this->info(__('awsync.info.populate_common'));
            $this->newline();

            $polygon->populateCommon($bar);

            $this->newLine(2);
            $this->info(__('awsync.info.done'));
            $this->newLine();

            $this->newline();
            $this->info(__('awsync.info.populate_named'));
            $this->newline();

            $polygon->populateNamed($bar);

            $this->newLine(2);
            $this->info(__('awsync.info.done'));
            $this->newLine();
        } catch (Throwable $th) {
            $this->error($th->getMessage());

            return 1;
        }

        $this->newLine();

        return 0;
    }
}
