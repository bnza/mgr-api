<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Zoo\Bone;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analyses_zoo_bones',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Bone::class,
    templateParentResourceName: 'zoo/bones',
    itemNormalizationGroups: ['zoo_bone:acl:read', 'zoo_bone_analysis:acl:read'])
]
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
class AnalysisZooBone extends BaseAnalysisJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'analysis_join_id_seq')]
    #[Groups([
        'analysis_join:acl:read',
        'zoo_bone_analysis:acl:read',
    ])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Bone::class, inversedBy: 'analyses')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'zoo_bone_analysis:acl:read',
        'analysis_join:acl:read',
        'analysis_join:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:analysis_join:create',
    ])]
    private Bone $subject;

    public function getSubject(): ?Bone
    {
        return $this->subject;
    }

    public function setSubject(?Bone $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
