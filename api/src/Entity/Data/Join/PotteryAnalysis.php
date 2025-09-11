<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\MediaObject;
use App\Entity\Data\Pottery;
use App\Entity\Vocabulary\Analysis\Type as AnalysisType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'pottery_analyses',
)]
#[ORM\UniqueConstraint(columns: ['item_id', 'analysis_type_id'])]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/analyses/potteries/{id}',
        ),
        new GetCollection('/analyses/potteries'),
        new GetCollection(
            uriTemplate: '/potteries/{parentId}/analyses',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'item',
                    fromClass: Pottery::class,
                ),
            ],
        ),
        new Post(
            uriTemplate: '/analyses/potteries',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:pottery_analysis:create']],
        ),
        new Patch(
            uriTemplate: '/analyses/potteries/{id}',
            security: 'is_granted("update", object)',
        ),
        new Delete(
            uriTemplate: '/analyses/potteries/{id}',
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: [
        'groups' => ['pottery_analysis:acl:read', 'media_object_join:read'],
    ],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'item.inventory', 'type.value', 'document.mimeType', 'rawData.mimeType', 'context.type.value']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'item.stratigraphicUnit.site' => 'exact',
        'item.stratigraphicUnit' => 'exact',
        'item.decorations.decoration' => 'exact',
        'item.inventory' => 'ipartial',
        'item.culturalContext' => 'exact',
        'item.chronologyLower' => 'exact',
        'item.chronologyUpper' => 'exact',
        'item.shape' => 'exact',
        'item.functionalGroup' => 'exact',
        'item.functionalForm' => 'exact',
        'item.notes' => 'ipartial',
        'item.surfaceTreatment' => 'exact',
        'item.innerColor' => 'ipartial',
        'item.outerColor' => 'ipartial',
        'item.decorationMotif' => 'ipartial',
        'type' => 'exact',
        'document.mimeType' => 'ipartial',
        'rawData.mimeType' => 'ipartial',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'summary',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'item.stratigraphicUnit.number',
        'item.stratigraphicUnit.year',
        'item.chronologyLower',
        'item.chronologyUpper',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'item.notes',
        'item.culturalContext',
        'item.chronologyLower',
        'item.chronologyUpper',
        'item.innerColor',
        'item.outerColor',
        'item.decorationMotif',
        'item.shape',
        'item.surfaceTreatment',
        'document',
        'rawData',
        'summary',
    ]
)]
#[UniqueEntity(
    fields: ['item', 'type'],
    message: 'Duplicate [pottery, analysis type] combination.',
    groups: ['validation:pottery_analysis:create']
)]
class PotteryAnalysis
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'pottery_analysis:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Pottery::class, inversedBy: 'analyses')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'pottery_analysis:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:pottery_analysis:create',
    ])]
    private Pottery $item;

    #[ORM\ManyToOne(targetEntity: AnalysisType::class)]
    #[ORM\JoinColumn(name: 'analysis_type_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'pottery:acl:read',
        'pottery_analysis:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:pottery_analysis:create',
    ])]
    private AnalysisType $type;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: true)]
    #[Groups([
        'pottery_analysis:acl:read',
    ])]
    private ?MediaObject $document = null;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(name: 'raw_data_id', referencedColumnName: 'id', nullable: true)]
    #[Groups([
        'pottery_analysis:acl:read',
    ])]
    private ?MediaObject $rawData = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'pottery_analysis:acl:read',
    ])]
    private ?string $summary = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getItem(): Pottery
    {
        return $this->item;
    }

    public function setItem(Pottery $item): PotteryAnalysis
    {
        $this->item = $item;

        return $this;
    }

    public function getType(): AnalysisType
    {
        return $this->type;
    }

    public function setType(AnalysisType $type): PotteryAnalysis
    {
        $this->type = $type;

        return $this;
    }

    public function getDocument(): ?MediaObject
    {
        return $this->document;
    }

    public function setDocument(?MediaObject $document): PotteryAnalysis
    {
        $this->document = $document;

        return $this;
    }

    public function getRawData(): ?MediaObject
    {
        return $this->rawData;
    }

    public function setRawData(?MediaObject $rawData): PotteryAnalysis
    {
        $this->rawData = $rawData;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): PotteryAnalysis
    {
        $this->summary = $summary;

        return $this;
    }
}
