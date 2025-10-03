<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Sample;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analyses_microstratigraphy',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Sample::class,
    templateParentResourceName: 'samples/microstratigraphy',
    itemNormalizationGroups: ['sample:acl:read', 'sample_microstratigraphy_analysis:acl:read'])
]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject.site' => 'exact',
        'subject.sampleStratigraphicUnits.stratigraphicUnits' => 'exact',
        'subject.sampleStratigraphicUnits.stratigraphicUnits.microstratigraphicUnit.identifier' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'subject.sampleStratigraphicUnits.stratigraphicUnit.number',
        'subject.sampleStratigraphicUnits.stratigraphicUnit.year',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'subject.description',
        'subject.sampleStratigraphicUnits.stratigraphicUnit.microstratigraphicUnit.notes',
    ]
)]
class AnalysisSampleMicrostratigraphy extends BaseAnalysisJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'analysis_join_id_seq')]
    #[Groups([
        'analysis_join:acl:read',
        'sample_microstratigraphy_analysis:acl:read',
    ])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Sample::class, inversedBy: 'analysesMicrostratigraphicUnits')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'sample_microstratigraphy_analysis:acl:read',
        'analysis_join:acl:read',
        'analysis_join:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:analysis_join:create',
    ])]
    private Sample $subject;

    public function getSubject(): ?Sample
    {
        return $this->subject;
    }

    public function setSubject(?Sample $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
