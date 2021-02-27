<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Faker;
use League\Flysystem\Filesystem;
use RuntimeException;
use Throwable;

abstract class Polygon
{
    protected string $folder;
    protected int $nums;
    protected Faker $faker;

    public function __construct(string $folder, int $quantity)
    {
        $this->faker = new Faker();
        $this->nums = ($quantity !== 0) ? $quantity : 1;
        $this->folder = ($folder !== '') ? $folder : '.';
    }

    public function putFiles(Filesystem $filesystem, int $number, string $prefix = '', $bar = null): void
    {
        if ($number === 0) {
            throw new RuntimeException(__('awsync.nonzero'));
        }

        if ($bar !== null) {
            $bar->setMaxSteps($number - 1);
            $bar->start();
        }

        for ($i = 0; $i < $number; $i++) {
            $fileName = $this->faker->getRandomFilename($prefix);
            $size = $this->faker->getRandomFilesize();

            try {
                $filesystem->put(sprintf("%s/%s", $this->folder, $fileName), random_bytes($size));

                if ($bar !== null) {
                    $bar->advance();
                }
            } catch (Throwable $th) {
                throw new RuntimeException(__('awsync.errors.populate', ['message' => $th->getMessage()]));
            }
        }

        if ($bar !== null) {
            $bar->finish();
        }
    }

    abstract protected function prepareFileSystem(): Filesystem;
    abstract public function getFileSystem(): Filesystem;
    abstract public function clean(bool $forceDelete): self;
    abstract public function populate(string $prefix, $bar): void;
}
