<?php

namespace App\Faker\Provider;

use Faker\Generator;
use Faker\Provider\Base as BaseProvider;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;

class GeographyPointProvider extends BaseProvider
{
    public function __construct(Generator $generator)
    {
        parent::__construct($generator);
    }

    /**
     * @throws InvalidValueException
     */
    public function makeGeoPoint(string $n, string $e): Point
    {
        return new Point($n, $e);
    }
}
