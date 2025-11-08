<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Individual;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisIndividual;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisJoin;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analysis_individuals',
)]
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
        'subject.stratigraphicUnit.site' => 'exact',
        'subject.stratigraphicUnit' => 'exact',
        'subject.sex' => 'exact',
        'subject.identifier' => 'ipartial',
        'subject.age.id' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'subject.stratigraphicUnit.number',
        'subject.stratigraphicUnit.year',
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
        'analysis_join:create',
        'individual:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:analysis_join:create'])]
    private ?Individual $subject = null;

    #[ORM\OneToOne(targetEntity: AbsDatingAnalysisIndividual::class, mappedBy: 'analysis', cascade: ['persist', 'remove'])]
    private AbsDatingAnalysisJoin $absDatingAnalysis;

    public function getSubject(): ?Individual
    {
        return $this->subject;
    }

    public function setSubject(?Individual $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
