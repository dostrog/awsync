<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Faker;

class PolygonCommon
{
    protected string $folder;
    protected Faker $faker;
    private int $commonQuantity;
    private int $namedQuantity;
    private $baseDir;
    private string $bucket;

    public function __construct(string $folder, int $maxCommon, int $maxNamed, $baseDir = null)
    {
        $this->faker = new Faker();
        $this->folder = $folder ?? '';
        $this->bucket = $this->folder;
        $this->baseDir = $baseDir;
        $this->commonQuantity = $this->faker->getRandomInt(1, $maxCommon);
        $this->namedQuantity = $this->faker->getRandomInt(1, $maxNamed);
    }

    public function populateCommon($bar = null): void
    {
        (new PolygonLocal($this->folder, $this->commonQuantity, $this->baseDir))->populate('common-', $bar);
        (new PolygonAWS($this->bucket, $this->commonQuantity, $this->baseDir))->populate('common-', $bar);
    }

    public function populateNamed($bar = null): void
    {
        (new PolygonLocal($this->folder, $this->namedQuantity, $this->baseDir))->populate('named-', $bar);
        (new PolygonAWS($this->bucket, $this->namedQuantity, $this->baseDir))->populate('named-', $bar);
    }
}
