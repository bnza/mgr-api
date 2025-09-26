<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Entity\Data\Zoo\Bone;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analysis_zoo_bones',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Bone::class,
    templateParentResourceName: 'zoo/bones',
    itemNormalizationGroups: ['zoo_bone:acl:read', 'zoo_bone_analysis:acl:read'])
]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject.stratigraphicUnit.site' => 'exact',
        'subject.stratigraphicUnit' => 'exact',
        'subject.species' => 'exact',
        'subject.element' => 'exact',
        'subject.part' => 'exact',
        'subject.side' => 'exact',
        'subject.species.family' => 'exact',
        'subject.species.class' => 'exact',
        'subject.species.scientificName' => 'ipartial',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'subject.stratigraphicUnit.number',
        'subject.stratigraphicUnit.year',
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
