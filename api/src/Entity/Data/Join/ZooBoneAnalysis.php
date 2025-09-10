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
use App\Entity\Data\Zoo\Bone;
use App\Entity\Vocabulary\Analysis\Type as AnalysisType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'zoo_bone_analyses',
)]
#[ORM\UniqueConstraint(columns: ['item_id', 'analysis_type_id'])]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/analyses/zoo/bones/{id}',
        ),
        new GetCollection('/analyses/zoo/bones'),
        new GetCollection(
            uriTemplate: '/zoo/bones/{parentId}/analyses',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'item',
                    fromClass: Bone::class,
                ),
            ],
        ),
        new Post(
            uriTemplate: '/analyses/zoo/bones',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:zoo_bone_analysis:create']],
        ),
        new Patch(
            uriTemplate: '/analyses/zoo/bones/{id}',
            security: 'is_granted("update", object)',
        ),
        new Delete(
            uriTemplate: '/analyses/zoo/bones/{id}',
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: [
        'groups' => ['zoo_bone_analysis:acl:read', 'media_object_join:read'],
    ],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'type.value', 'document.mimeType', 'rawData.mimeType', 'context.type.value']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'item.stratigraphicUnit.site' => 'exact',
        'item.stratigraphicUnit' => 'exact',
        'item.decorations.decoration' => 'exact',
        'item.species' => 'exact',
        'item.element' => 'exact',
        'item.part' => 'exact',
        'item.side' => 'exact',
        'item.species.family' => 'exact',
        'item.species.class' => 'exact',
        'item.species.scientificName' => 'ipartial',
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
        'zoo_bone.stratigraphicUnit.number',
        'zoo_bone.stratigraphicUnit.year',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'document',
        'rawData',
        'summary',
    ]
)]
#[UniqueEntity(
    fields: ['item', 'type'],
    message: 'Duplicate [zoo bone, analysis type] combination.',
    groups: ['validation:zoo_bone_analysis:create']
)]
class ZooBoneAnalysis
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'zoo_bone_analysis:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Bone::class, inversedBy: 'analyses')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'zoo_bone_analysis:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:zoo_bone_analysis:create',
    ])]
    private Bone $item;

    #[ORM\ManyToOne(targetEntity: AnalysisType::class)]
    #[ORM\JoinColumn(name: 'analysis_type_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'zoo_bone:acl:read',
        'zoo_bone_analysis:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:zoo_bone_analysis:create',
    ])]
    private AnalysisType $type;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: true)]
    #[Groups([
        'zoo_bone_analysis:acl:read',
    ])]
    private ?MediaObject $document = null;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(name: 'raw_data_id', referencedColumnName: 'id', nullable: true)]
    #[Groups([
        'zoo_bone_analysis:acl:read',
    ])]
    private ?MediaObject $rawData = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'zoo_bone_analysis:acl:read',
    ])]
    private ?string $summary = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getItem(): Bone
    {
        return $this->item;
    }

    public function setItem(Bone $item): ZooBoneAnalysis
    {
        $this->item = $item;

        return $this;
    }

    public function getType(): AnalysisType
    {
        return $this->type;
    }

    public function setType(AnalysisType $type): ZooBoneAnalysis
    {
        $this->type = $type;

        return $this;
    }

    public function getDocument(): ?MediaObject
    {
        return $this->document;
    }

    public function setDocument(?MediaObject $document): ZooBoneAnalysis
    {
        $this->document = $document;

        return $this;
    }

    public function getRawData(): ?MediaObject
    {
        return $this->rawData;
    }

    public function setRawData(?MediaObject $rawData): ZooBoneAnalysis
    {
        $this->rawData = $rawData;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): ZooBoneAnalysis
    {
        $this->summary = $summary;

        return $this;
    }
}
