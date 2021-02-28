<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Faker;
use League\Flysystem\Filesystem;
use RuntimeException;
use Throwable;

class PolygonCommon
{
    protected string $folder;
    protected Faker $faker;
    private int $commonQuantity;
    private int $namedQuantity;
    private $baseDir;
    private string $bucket;
    /**
     * @var Filesystem
     */
    private Filesystem $localFilesystem;
    /**
     * @var Filesystem
     */
    private Filesystem $awsFilesystem;

    public function __construct(string $folder, int $maxCommon, int $maxNamed, $baseDir = null)
    {
        $this->faker = new Faker();
        $this->folder = $folder ?? '';
        $this->bucket = $this->folder;
        $this->baseDir = $baseDir;
        $this->commonQuantity = $this->faker->getRandomInt(1, $maxCommon);
        $this->namedQuantity = $this->faker->getRandomInt(1, $maxNamed);
        $this->localFilesystem = (new PolygonLocal($this->folder, $this->commonQuantity, $this->baseDir))->getFileSystem();
        $this->awsFilesystem = (new PolygonAWS($this->bucket, $this->commonQuantity))->getFileSystem();
    }

    private function populateFiles(int $quantity, string $prefix, bool $keepSize, $bar = null): void
    {
        if ($bar !== null) {
            $bar->setMaxSteps($quantity - 1);
            $bar->start();
        }

        for ($i = 0; $i < $quantity; $i++) {
            $fileName = $this->faker->getRandomFilename($prefix);
            $size = $this->faker->getRandomFilesize();

            try {
                $this->localFilesystem->put(sprintf("%s/%s", $this->folder, $fileName), random_bytes($size));
                $size = ($keepSize) ? $size :  $this->faker->getRandomFilesize();
                $this->awsFilesystem->put(sprintf("%s/%s", '', $fileName), random_bytes($size));

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

    public function populateCommon($bar = null): void
    {
        $this->populateFiles($this->commonQuantity, 'common-', true, $bar);
    }

    public function populateNamed($bar = null): void
    {
        $this->populateFiles($this->namedQuantity, 'named-', false, $bar);
    }
}
