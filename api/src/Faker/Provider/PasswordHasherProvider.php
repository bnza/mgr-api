<?php

namespace App\Faker\Provider;

use App\Entity\Auth\User;
use Faker\Generator;
use Faker\Provider\Base as BaseProvider;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordHasherProvider extends BaseProvider
{
    public function __construct(Generator $generator, private readonly UserPasswordHasherInterface $hasher)
    {
        parent::__construct($generator);
    }

    public function hashPassword(string $plainPassword): string
    {
        return $this->hasher->hashPassword(new User(), $plainPassword);
    }
}
