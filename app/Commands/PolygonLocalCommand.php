<?php
declare(strict_types=1);

namespace App\Commands;

use App\Helpers\Faker;
use App\Services\PolygonLocal;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class PolygonLocalCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'polygon:local
    {folder : Folder name where files will be stored}
    {max_num=7 : Max number of files}
    {--baseDir= : Base working directory, "./assets" if not specified}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Populate test files with random data on local filesystem';

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
            (new PolygonLocal($this->argument('folder'), $quantity, $this->option('baseDir')))
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
