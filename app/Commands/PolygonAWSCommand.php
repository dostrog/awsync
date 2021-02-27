<?php
declare(strict_types=1);

namespace App\Commands;

use App\Helpers\Faker;
use App\Services\PolygonAWS;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class PolygonAWSCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = "polygon:aws
    {bucket : Bucket name on AWS S3 where files will be stored}
    {max_num=11 : Number of files}
    {--folder= : Folder name where files will be stored}";

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Populate test files with random data on AWS S3';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $quantity = (new Faker())->getRandomInt(1, (int)$this->argument('max_num'));

        $this->newline();
        $this->info($this->description . '...');
        $this->newline();

        try {
            (new PolygonAWS($this->argument('bucket'), $quantity, $this->option('folder')))
                ->clean()
                ->populate('', $this->output->createProgressBar());
        } catch (Throwable $th) {
            $this->error($th->getMessage());

            return 1;
        }

        $this->newLine(2);
        $this->info('done.');
        $this->newLine();

        return 0;
    }
}
