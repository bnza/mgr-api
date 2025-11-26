<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\Granted\GrantedAnalysisFilter;
use App\Doctrine\Filter\SearchAnalysisFilter;
use App\Entity\Auth\User;
use App\Entity\Data\Join\MediaObject\MediaObjectAnalysis;
use App\Entity\Vocabulary\Analysis\Type;
use App\State\AnalysisPostProcessor;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analyses',
)]
#[ORM\UniqueConstraint(fields: ['analysis_type_id', 'identifier'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/analyses/{id}',
            requirements: ['id' => '\d+'],
        ),
        new GetCollection(
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
        new Patch(
            security: 'is_granted("update", object)',
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:analysis:create']],
            processor: AnalysisPostProcessor::class
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['analysis:acl:read']],
    denormalizationContext: ['groups' => ['analysis:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'year',
    'type.value',
    'identifier',
    'responsible',
    'status',
    'laboratory',
    'summary',
    'createdBy.email',
])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'type' => 'exact',
        'year' => 'exact',
        'type.group' => 'exact',
        'type.code' => 'exact',
        'identifier' => 'ipartial',
        'responsible' => 'ipartial',
        'laboratory' => 'ipartial',
        'summary' => 'ipartial',
        'createdBy.email' => 'exact',
        'status' => 'exact',
        'mediaObjects.mediaObject.originalFilename' => 'ipartial',
        'mediaObjects.mediaObject.mimeType' => 'ipartial',
        'mediaObjects.mediaObject.type.group' => 'exact',
        'mediaObjects.mediaObject.type' => 'exact',
        'mediaObjects.mediaObject.uploadedBy.email' => 'ipartial',
        'mediaObjects.mediaObject.uploadDate' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'year' => 'exact',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'laboratory',
        'summary',
        'responsible',
        'mediaObjects',
    ]
)]
#[ApiFilter(SearchAnalysisFilter::class)]
#[ApiFilter(GrantedAnalysisFilter::class)]
class Analysis
{
    public const string GROUP_ABS_DATING = 'absolute dating';
    public const string GROUP_ASSEMBLAGE = 'assemblage';
    public const string GROUP_MATERIAL_ANALYSIS = 'material analysis';
    public const string GROUP_MICROMORPHOLOGY = 'micromorphology';
    public const string GROUP_MICROSCOPE = 'microscope';
    public const string GROUP_SEDIMENT_CORES = 'sediment cores';

    public const string TYPE_C14 = 'C14';
    public const string TYPE_THL = 'THL';
    public const string TYPE_ANTHRA = 'ANTHRA';
    public const string TYPE_ANTHRO = 'ANTHRO';
    public const string TYPE_CARP = 'CARP';
    public const string TYPE_ZOO = 'ZOO';
    public const string TYPE_ADNA = 'ADNA';
    public const string TYPE_ISO = 'ISO';
    public const string TYPE_ORA = 'ORA';
    public const string TYPE_XRF = 'XRF';
    public const string TYPE_XRD = 'XRD';
    public const string TYPE_THS = 'THS';
    public const string TYPE_OPT = 'OPT';
    public const string TYPE_SEM = 'SEM';
    public const string TYPE_POL = 'POL';
    public const string TYPE_SDNA = 'SDNA';

    public const array TYPES = [
        // Generated from fixtures: fixtures/vocabulary.analysis_type.yml
        self::TYPE_C14 => [
            'group' => self::GROUP_ABS_DATING,
            'value' => 'C14',
        ],
        self::TYPE_THL => [
            'group' => self::GROUP_ABS_DATING,
            'value' => 'thermoluminescence',
        ],
        self::TYPE_ANTHRA => [
            'group' => self::GROUP_ASSEMBLAGE,
            'value' => 'anthracology',
        ],
        self::TYPE_ANTHRO => [
            'group' => self::GROUP_ASSEMBLAGE,
            'value' => 'anthropology',
        ],
        self::TYPE_CARP => [
            'group' => self::GROUP_ASSEMBLAGE,
            'value' => 'carpology',
        ],
        self::TYPE_ZOO => [
            'group' => self::GROUP_ASSEMBLAGE,
            'value' => 'zooarchaeology',
        ],
        self::TYPE_ADNA => [
            'group' => self::GROUP_MATERIAL_ANALYSIS,
            'value' => 'aDNA',
        ],
        self::TYPE_ISO => [
            'group' => self::GROUP_MATERIAL_ANALYSIS,
            'value' => 'isotopes',
        ],
        self::TYPE_ORA => [
            'group' => self::GROUP_MATERIAL_ANALYSIS,
            'value' => 'ORA',
        ],
        self::TYPE_XRF => [
            'group' => self::GROUP_MATERIAL_ANALYSIS,
            'value' => 'XRF',
        ],
        self::TYPE_XRD => [
            'group' => self::GROUP_MATERIAL_ANALYSIS,
            'value' => 'XRD',
        ],
        self::TYPE_THS => [
            'group' => self::GROUP_MICROMORPHOLOGY,
            'value' => 'thin section',
        ],
        self::TYPE_OPT => [
            'group' => self::GROUP_MICROSCOPE,
            'value' => 'optical',
        ],
        self::TYPE_SEM => [
            'group' => self::GROUP_MICROSCOPE,
            'value' => 'SEM',
        ],
        self::TYPE_POL => [
            'group' => self::GROUP_SEDIMENT_CORES,
            'value' => 'pollen',
        ],
        self::TYPE_SDNA => [
            'group' => self::GROUP_SEDIMENT_CORES,
            'value' => 'sedimentary DNA',
        ],
    ];
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'analysis:acl:read',
        'analysis:export',
    ])]
    #[ApiProperty(required: true)]
    private int $id;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'abs_dating_analysis:read',
        'analysis:acl:read',
        'analysis:create',
        'analysis:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:analysis:create',
    ])]
    #[ApiProperty(required: true)]
    private string $identifier;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'abs_dating_analysis:read',
        'analysis:acl:read',
        'analysis:create',
        'analysis:export',
    ])]
    #[ApiProperty(required: true)]
    private int $status = 0;

    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'analysis_type_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'abs_dating_analysis:read',
        'analysis:acl:read',
        'analysis:create',
        'analysis:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:analysis:create',
    ])]
    #[ApiProperty(required: true)]
    private Type $type;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'abs_dating_analysis:read',
        'analysis:acl:read',
        'analysis:create',
        'analysis:export',
    ])]
    private ?string $responsible;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotBlank(groups: [
        'validation:analysis:create',
    ])]
    #[Assert\Sequentially([
        new Assert\GreaterThanOrEqual(value: 2000),
        new AppAssert\IsLessThanOrEqualToCurrentYear(),
    ],
        groups: ['validation:analysis:create'])
    ]
    #[Groups([
        'abs_dating_analysis:read',
        'analysis:acl:read',
        'analysis:export',
        'analysis:create',
    ])]
    #[ApiProperty(required: true)]
    private int $year;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'abs_dating_analysis:read',
        'analysis:acl:read',
        'analysis:export',
        'analysis:create',
    ])]
    private ?string $laboratory;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'analysis:acl:read',
        'analysis:create',
        'analysis:export',
    ])]
    private ?string $summary = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    #[Groups([
        'analysis:acl:read',
    ])]
    private ?User $createdBy = null;

    #[ORM\OneToMany(
        targetEntity: MediaObjectAnalysis::class,
        mappedBy: 'item',
        orphanRemoval: true
    )]
    private Collection $mediaObjects;

    public function __construct()
    {
        $this->mediaObjects = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Analysis
    {
        $this->id = $id;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): Analysis
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): Analysis
    {
        $this->type = $type;

        return $this;
    }

    public function getResponsible(): ?string
    {
        return $this->responsible;
    }

    public function setResponsible(?string $responsible): Analysis
    {
        $this->responsible = $responsible;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): Analysis
    {
        $this->summary = $summary;

        return $this;
    }

    public function getMediaObjects(): Collection
    {
        return $this->mediaObjects;
    }

    public function setMediaObjects(Collection $mediaObjects): Analysis
    {
        $this->mediaObjects = $mediaObjects;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): Analysis
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): Analysis
    {
        $this->year = $year;

        return $this;
    }

    public function getLaboratory(): ?string
    {
        return $this->laboratory;
    }

    public function setLaboratory(?string $laboratory): Analysis
    {
        $this->laboratory = $laboratory;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): Analysis
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCode(): string
    {
        return sprintf('%s.%s', $this->getType()->code, $this->getIdentifier());
    }
}
