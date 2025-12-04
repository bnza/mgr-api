<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\Granted\GrantedParentAnalysisSubjectFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Analysis;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @template T of object
 */
#[ORM\MappedSuperclass]
#[ORM\UniqueConstraint(columns: ['subject_id', 'analysis_id'])]
#[UniqueEntity(
    fields: ['subject', 'analysis'],
    message: 'Duplicate [subject, analysis] combination.',
    groups: ['validation:analysis_join:create'])
]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'analysis.type.group', 'analysis.type.value', 'analysis.identifier']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'analysis.createdBy.email' => 'exact',
        'analysis.identifier' => 'ipartial',
        'analysis.laboratory' => 'ipartial',
        'analysis.responsible' => 'ipartial',
        'analysis.status' => 'exact',
        'analysis.summary' => 'ipartial',
        'analysis.type' => 'exact',
        'analysis.type.code' => 'exact',
        'analysis.type.group' => 'exact',
        'analysis.year' => 'exact',
        'subject.stratigraphicUnit.area' => 'exact',
        'subject.stratigraphicUnit.building' => 'exact',
        'subject.stratigraphicUnit.chronologyLower' => 'exact',
        'subject.stratigraphicUnit.chronologyUpper' => 'exact',
        'subject.stratigraphicUnit.number' => 'exact',
        'subject.stratigraphicUnit.site' => 'exact',
        'subject.stratigraphicUnit.year' => 'exact',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'summary',
        'analysis.laboratory',
        'analysis.responsible',
        'analysis.summary',
        'subject.notes',
        'subject.stratigraphicUnit.chronologyLower',
        'subject.stratigraphicUnit.chronologyUpper',
        'subject.stratigraphicUnit.description',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'analysis.year',
        'subject.stratigraphicUnit.chronologyLower',
        'subject.stratigraphicUnit.chronologyUpper',
        'subject.stratigraphicUnit.number',
        'subject.stratigraphicUnit.year',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'summary',
        'analysis.name',
        'analysis.summary',
        'subject.notes',
        'subject.stratigraphicUnit.interpretation',
        'subject.stratigraphicUnit.description',
    ]
)]
#[ApiFilter(
    GrantedParentAnalysisSubjectFilter::class,
)]
abstract class BaseAnalysisJoin
{
    /** @return string[] */
    abstract public static function getPermittedAnalysisTypes(): array;

    // You must define #[ORM\Id],  #[ORM\GeneratedValue] and #[ORM\Column] in the subclass to share the same generator
    // For serialization contexts @see MediaObjectJoinApiResource::class
    #[Groups(['analysis_join:acl:read'])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Analysis::class)]
    #[ORM\JoinColumn(name: 'analysis_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['analysis_join:acl:read', 'analysis_join:create', 'analysis_join:export'])]
    #[Assert\NotBlank(groups: ['validation:analysis_join:create'])]
    #[AppAssert\PermittedAnalysisType(groups: ['validation:analysis_join:create'])]
    protected Analysis $analysis;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['analysis_join:acl:read', 'analysis_join:create', 'analysis_join:update', 'analysis_join:export'])]
    protected ?string $summary = null;

    /** @return T */
    abstract public function getSubject(): ?object;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getAnalysis(): Analysis
    {
        return $this->analysis;
    }

    public function setAnalysis(Analysis $analysis): self
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): BaseAnalysisJoin
    {
        $this->summary = $summary ?? null;

        return $this;
    }
}
