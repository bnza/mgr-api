<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\BitmapFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Analysis;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisJoin;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisZooTooth;
use App\Entity\Data\Zoo\Tooth;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use App\Metadata\Attribute\SubResourceFilters\ApiStratigraphicUnitSubresourceFilters;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analysis_zoo_teeth',
)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'analysis',
        inversedBy: 'subjectZooTeeth'
    ),
])]
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
        'subject.element' => 'exact',
        'subject.side' => 'exact',
        'subject.taxonomy' => 'exact',
        'subject.taxonomy.class' => 'exact',
        'subject.taxonomy.code' => 'exact',
        'subject.taxonomy.family' => 'exact',
        'subject.taxonomy.vernacularName' => 'ipartial',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'summary',
    ]
)]
#[ApiFilter(ExistsFilter::class, properties: [
    'subject.taxonomy.family',
])]
#[ApiFilter(BitmapFilter::class, properties: [
    'subject.connected',
])]
#[ApiStratigraphicUnitSubresourceFilters('subject.stratigraphicUnit')]
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
        'zoo_tooth_analysis:export',
    ])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Tooth::class, inversedBy: 'analyses')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'analysis_join:acl:read',
        'analysis_join:create',
        'zoo_tooth_analysis:acl:read',
        'zoo_tooth_analysis:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:analysis_join:create',
    ])]
    private Tooth $subject;

    #[ORM\OneToOne(targetEntity: AbsDatingAnalysisZooTooth::class, mappedBy: 'analysis', cascade: ['persist', 'remove'])]
    #[Groups([
        'zoo_tooth_analysis:acl:read',
        'zoo_tooth_analysis:export',
        'analysis_join:acl:read',
        'analysis_join:create',
        'analysis_join:update',
    ])]
    private ?AbsDatingAnalysisJoin $absDatingAnalysis;

    public function getSubject(): ?Tooth
    {
        return $this->subject;
    }

    public function setSubject(Tooth $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getAbsDatingAnalysis(): ?AbsDatingAnalysisJoin
    {
        return $this->absDatingAnalysis;
    }

    public function setAbsDatingAnalysis(?AbsDatingAnalysisJoin $absDatingAnalysis): self
    {
        $this->absDatingAnalysis = $absDatingAnalysis;
        $absDatingAnalysis?->setAnalysis($this);

        return $this;
    }

    public static function getPermittedAnalysisTypes(): array
    {
        return array_keys(
            array_filter(
                Analysis::TYPES,
                fn ($type) => in_array(
                    $type['group'],
                    [
                        Analysis::GROUP_ABS_DATING,
                        Analysis::GROUP_MICROSCOPE,
                        Analysis::GROUP_MATERIAL_ANALYSIS,
                    ]
                )
            )
        );
    }
}
