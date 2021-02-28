<?php
declare(strict_types=1);

namespace App\Helpers;

use Faker\Factory;
use Illuminate\Support\Str;
use Faker\Generator;

final class Faker
{
    protected Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function getRandomFilename(string $prefix = '', int $basenameLength = 32, int $extensionName = 3): string
    {
        return sprintf("%s%s.%s", $prefix, Str::random($basenameLength), Str::random($extensionName));
    }

    /**
     * @param int $from
     * @param int $to
     * @param int $multiplier
     * @return int in KBytes by default
     */
    public function getRandomFilesize(int $from = 1, int $to = 100, int $multiplier = 1024): int
    {
        return $this->getRandomInt($from, $to, $multiplier);
    }

    public function getRandomInt(int $from = 1, int $to = 100, int $multiplier = 1): int
    {
        return $this->faker->numberBetween($from, $to) * $multiplier;
    }
}
