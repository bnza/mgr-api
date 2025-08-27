<?php

namespace App\Resource\Validator;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Data\Context;
use App\Entity\Data\Join\ContextSample;
use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Entity\Data\Join\MediaObject\MediaObjectStratigraphicUnit;
use App\Entity\Data\Join\PotteryAnalysis;
use App\Entity\Data\Join\SampleStratigraphicUnit;
use App\Entity\Data\Pottery;
use App\Entity\Data\Sample;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use App\State\ValidatorUniqueProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/validator/unique/sites/code/{id}',
            defaults: [
                'resource' => Site::class,
            ],
            requirements: [
                'code' => '[a-zA-Z0-9]+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/sites/name/{id}',
            defaults: [
                'resource' => Site::class,
            ],
            requirements: [
                'name' => '.+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/potteries/inventory/{id}',
            defaults: [
                'resource' => Pottery::class,
            ],
            requirements: [
                'inventory' => '\.+',
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
        new Get(
            uriTemplate: '/validator/unique/stratigraphic_units/{site}/{year}/{number}',
            defaults: [
                'resource' => StratigraphicUnit::class,
            ],
            requirements: [
                'site' => '\d+',
                'year' => '\d+',
                'number' => '\d+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/context_sample/{context}/{sample}',
            defaults: [
                'resource' => ContextSample::class,
            ],
            requirements: [
                'context' => '\d+',
                'sample' => '\d+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/context_stratigraphic_units/{context}/{stratigraphicUnit}',
            defaults: [
                'resource' => ContextStratigraphicUnit::class,
            ],
            requirements: [
                'context' => '\d+',
                'stratigraphicUnit' => '\d+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/contexts/{site}/{name}',
            defaults: [
                'resource' => Context::class,
            ],
            requirements: [
                'site' => '\d+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/samples/{site}/{type}/{year}/{number}',
            defaults: [
                'resource' => Sample::class,
            ],
            requirements: [
                'site' => '\d+',
                'type' => '\d+',
                'year' => '\d+',
                'number' => '\d+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/sample_stratigraphic_units/{sample}/{stratigraphicUnit}',
            defaults: [
                'resource' => SampleStratigraphicUnit::class,
            ],
            requirements: [
                'context' => '\d+',
                'sample' => '\d+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/potteries/{pottery}/{type}',
            defaults: [
                'resource' => PotteryAnalysis::class,
            ],
            requirements: [
                'pottery' => '\d+',
                'type' => '\d+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/media_objects/stratigraphic_units/{mediaObject}/{item}',
            defaults: [
                'resource' => MediaObjectStratigraphicUnit::class,
            ],
            requirements: [
                'mediaObject' => '[a-f0-9]{64}',
                'item' => '\d+',
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/media_objects/stratigraphic_units/{mediaObject}/{item}',
            defaults: [
                'resource' => MediaObjectStratigraphicUnit::class,
            ],
            requirements: [
                'mediaObject' => '\d+',
                'item' => '\d+',
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
            default => array_key_exists($name, $this->criteria) ? $this->criteria[$name] : null,
        };
    }
}
