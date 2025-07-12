<?php

namespace App\Resource\Validator;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Data\Site;
use App\State\ValidatorUniqueProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/validator/unique/site/code/{id}',
            defaults: [
                'resource' => Site::class,
            ],
            requirements: [
                'code' => '[a-zA-Z0-9]+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/site_user_privileges/{site}/{user}',
            defaults: [
                'resource' => SiteUserPrivilege::class,
            ],
            requirements: [
                'site' => '\d+',
                'user' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
            ],
        ),

    ],
    provider: ValidatorUniqueProvider::class,
)]
readonly class UniqueValidator
{
    public function __construct(private array $criteria, public int $valid)
    {
    }

    public function __get(string $name)
    {
        return match ($name) {
            'id' => implode('.', array_values($this->criteria)),
            default => $this->criteria[$name]
        };
    }

//    public function getId(): string
//    {
//        return implode('.', array_values($this->criteria));
//    }
}
