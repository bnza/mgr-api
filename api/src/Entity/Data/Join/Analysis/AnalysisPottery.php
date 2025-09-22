<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Pottery;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'pottery_analyses',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Pottery::class,
    templateParentResourceName: 'potteries',
    itemNormalizationGroups: ['analysis_pottery:acl:read', 'pottery:acl:read'])
]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'item.inventory', 'type.value', 'document.mimeType', 'rawData.mimeType', 'context.type.value']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'item.stratigraphicUnit.site' => 'exact',
        'item.stratigraphicUnit' => 'exact',
        'item.decorations.decoration' => 'exact',
        'item.inventory' => 'ipartial',
        'item.culturalContext' => 'exact',
        'item.chronologyLower' => 'exact',
        'item.chronologyUpper' => 'exact',
        'item.shape' => 'exact',
        'item.functionalGroup' => 'exact',
        'item.functionalForm' => 'exact',
        'item.notes' => 'ipartial',
        'item.surfaceTreatment' => 'exact',
        'item.innerColor' => 'ipartial',
        'item.outerColor' => 'ipartial',
        'item.decorationMotif' => 'ipartial',
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
        'item.stratigraphicUnit.number',
        'item.stratigraphicUnit.year',
        'item.chronologyLower',
        'item.chronologyUpper',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'item.notes',
        'item.culturalContext',
        'item.chronologyLower',
        'item.chronologyUpper',
        'item.innerColor',
        'item.outerColor',
        'item.decorationMotif',
        'item.shape',
        'item.surfaceTreatment',
        'document',
        'rawData',
        'summary',
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

    public function getSubject(): ?Pottery
    {
        return $this->subject;
    }

    public function setSubject(?Pottery $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
