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
        'mediaObjectsAnalysis.mediaObject.originalFilename' => 'ipartial',
        'mediaObjectsAnalysis.mediaObject.mimeType' => 'ipartial',
        'mediaObjectsAnalysis.mediaObject.type.group' => 'exact',
        'mediaObjectsAnalysis.mediaObject.type' => 'exact',
        'mediaObjectsAnalysis.mediaObject.uploadedBy.email' => 'ipartial',
        'mediaObjectsAnalysis.mediaObject.uploadDate' => 'exact',
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
        'mediaObjectsAnalysis',
    ]
)]
#[ApiFilter(SearchAnalysisFilter::class)]
#[ApiFilter(GrantedAnalysisFilter::class)]
class Analysis
{
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
    private Collection $mediaObjectsAnalysis;

    public function __construct()
    {
        $this->mediaObjectsAnalysis = new ArrayCollection();
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

    public function getMediaObjectsAnalysis(): Collection
    {
        return $this->mediaObjectsAnalysis;
    }

    public function setMediaObjectsAnalysis(Collection $mediaObjectsAnalysis): Analysis
    {
        $this->mediaObjectsAnalysis = $mediaObjectsAnalysis;

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
