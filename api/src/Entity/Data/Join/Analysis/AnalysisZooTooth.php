<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Zoo\Tooth;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analyses_zoo_teeth',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Tooth::class,
    templateParentResourceName: 'zoo/teeth',
    itemNormalizationGroups: ['zoo_bone:acl:read', 'zoo_tooth_analysis:acl:read'])
]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject.stratigraphicUnit.site' => 'exact',
        'subject.stratigraphicUnit' => 'exact',
        'subject.decorations.decoration' => 'exact',
        'subject.species' => 'exact',
        'subject.element' => 'exact',
        'subject.side' => 'exact',
        'subject.species.family' => 'exact',
        'subject.species.class' => 'exact',
        'subject.species.scientificName' => 'ipartial',
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
        'zoo_tooth.stratigraphicUnit.number',
        'zoo_tooth.stratigraphicUnit.year',
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
class AnalysisZooTooth extends BaseAnalysisJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'analysis_join_id_seq')]
    #[Groups([
        'analysis_join:acl:read',
        'zoo_tooth_analysis:acl:read',
    ])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Tooth::class, inversedBy: 'analyses')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'analysis_join:acl:read',
        'analysis_join:create',
        'zoo_tooth_analysis:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:analysis_join:create',
    ])]
    private Tooth $subject;

    public function getSubject(): ?Tooth
    {
        return $this->subject;
    }

    public function setSubject(Tooth $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
