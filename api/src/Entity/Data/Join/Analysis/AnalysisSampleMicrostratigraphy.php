<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Analysis;
use App\Entity\Data\Sample;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use App\Metadata\Attribute\SubResourceFilters\ApiStratigraphicUnitSubresourceFilters;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analyses_microstratigraphy',
)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'analysis',
        inversedBy: 'subjectSampleMicrostratigraphy'
    ),
])]
#[ApiAnalysisJoinResource(
    subjectClass: Sample::class,
    templateParentResourceName: 'microstratigraphy',
    itemNormalizationGroups: ['sample:acl:read', 'sample_microstratigraphy_analysis:acl:read'],
    templateParentCategoryName: 'samples'
)
]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject.site' => 'exact',
        'subject.sampleStratigraphicUnits.stratigraphicUnit' => 'exact',
        'subject.sampleStratigraphicUnits.stratigraphicUnit.microstratigraphicUnits.identifier' => 'exact',
        'subject.sampleStratigraphicUnits.stratigraphicUnit.microstratigraphicUnits.notes' => 'ipartial',
        'subject.type' => 'exact',
        'subject.year' => 'exact',
        'subject.number' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'subject.year',
        'subject.number',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'subject.description',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'subject.description',
        'subject.sampleStratigraphicUnits.stratigraphicUnit.microstratigraphicUnits.notes',
    ]
)]
#[ApiStratigraphicUnitSubresourceFilters('subject.sampleStratigraphicUnits.stratigraphicUnit')]
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
        'sample_microstratigraphy_analysis:export',
    ])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Sample::class, inversedBy: 'analysesMicrostratigraphicUnits')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'sample_microstratigraphy_analysis:acl:read',
        'sample_microstratigraphy_analysis:export',
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

    public static function getPermittedAnalysisTypes(): array
    {
        return array_keys(
            array_filter(Analysis::TYPES, fn ($type) => in_array($type['group'], [Analysis::GROUP_MICROMORPHOLOGY]))
        );
    }
}
