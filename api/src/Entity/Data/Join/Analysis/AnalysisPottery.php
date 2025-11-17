<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisJoin;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisPottery;
use App\Entity\Data\Pottery;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analysis_potteries',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Pottery::class,
    templateParentResourceName: 'potteries',
    itemNormalizationGroups: ['analysis_pottery:acl:read', 'pottery:acl:read'])
]
#[ApiFilter(
    OrderFilter::class,
    properties: ['subject.inventory']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject.stratigraphicUnit.site' => 'exact',
        'subject.stratigraphicUnit' => 'exact',
        'subject.decorations.decoration' => 'exact',
        'subject.inventory' => 'ipartial',
        'subject.culturalContext' => 'exact',
        'subject.chronologyLower' => 'exact',
        'subject.chronologyUpper' => 'exact',
        'subject.shape' => 'exact',
        'subject.functionalGroup' => 'exact',
        'subject.functionalForm' => 'exact',
        'subject.notes' => 'ipartial',
        'subject.surfaceTreatment' => 'exact',
        'subject.innerColor' => 'ipartial',
        'subject.outerColor' => 'ipartial',
        'subject.decorationMotif' => 'ipartial',
        'subject.type' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'subject.stratigraphicUnit.number',
        'subject.stratigraphicUnit.year',
        'subject.chronologyLower',
        'subject.chronologyUpper',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'subject.notes',
        'subject.culturalContext',
        'subject.chronologyLower',
        'subject.chronologyUpper',
        'subject.innerColor',
        'subject.outerColor',
        'subject.decorationMotif',
        'subject.shape',
        'subject.surfaceTreatment',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'subject.notes',
    ]
)]
class AnalysisPottery extends BaseAnalysisJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'analysis_join_id_seq')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Pottery::class, inversedBy: 'analyses')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'analysis_join:acl:read',
        'analysis_join:create',
        'pottery:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:analysis_join:create'])]
    private ?Pottery $subject = null;

    #[ORM\OneToOne(targetEntity: AbsDatingAnalysisPottery::class, mappedBy: 'analysis', cascade: ['persist', 'remove'])]
    #[Groups([
        'analysis_pottery:acl:read',
        'analysis_join:acl:read',
        'analysis_join:create',
        'analysis_join:update',
    ])]
    private ?AbsDatingAnalysisJoin $absDatingAnalysis;

    public function getSubject(): ?Pottery
    {
        return $this->subject;
    }

    public function setSubject(?Pottery $subject): self
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
}
