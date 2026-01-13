<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Analysis;
use App\Entity\Data\Individual;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisIndividual;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisJoin;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use App\Metadata\Attribute\SubResourceFilters\ApiStratigraphicUnitSubresourceFilters;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analysis_individuals',
)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'analysis',
        inversedBy: 'subjectIndividuals'
    ),
])]
#[ApiAnalysisJoinResource(
    subjectClass: Individual::class,
    templateParentResourceName: 'individuals',
    itemNormalizationGroups: ['analysis_individual:acl:read', 'individual:acl:read'])
]
#[ApiFilter(
    OrderFilter::class,
    properties: ['subject.inventory']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject.sex' => 'exact',
        'subject.identifier' => 'ipartial',
        'subject.age' => 'exact',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'subject.notes',
        'subject.age',
        'subject.sex',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'subject.notes',
    ]
)]
#[ApiStratigraphicUnitSubresourceFilters('subject.stratigraphicUnit')]
class AnalysisIndividual extends BaseAnalysisJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'analysis_join_id_seq')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Individual::class, inversedBy: 'analyses')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'analysis_join:acl:read',
        'analysis_individual:export',
        'analysis_join:create',
        'individual:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:analysis_join:create'])]
    private ?Individual $subject = null;

    #[ORM\OneToOne(targetEntity: AbsDatingAnalysisIndividual::class, mappedBy: 'analysis', cascade: ['persist', 'remove'])]
    #[Groups([
        'analysis_individual:acl:read',
        'analysis_individual:export',
        'analysis_join:acl:read',
        'analysis_join:create',
        'analysis_join:update',
    ])]
    private ?AbsDatingAnalysisJoin $absDatingAnalysis;

    public function getSubject(): ?Individual
    {
        return $this->subject;
    }

    public function setSubject(?Individual $subject): self
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
