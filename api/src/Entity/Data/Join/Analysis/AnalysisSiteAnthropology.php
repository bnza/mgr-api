<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Site;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analyses_anthropology',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Site::class,
    templateParentResourceName: 'anthropology',
    itemNormalizationGroups: ['site:acl:read', 'site_anthropology:acl:read'],
    templateParentCategoryName: 'sites'
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject' => 'exact',
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
class AnalysisSiteAnthropology extends BaseAnalysisJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'analysis_join_id_seq')]
    #[Groups([
        'analysis_join:acl:read',
        'site_anthropology:acl:read',
    ])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: 'analysesAnthropology')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'site_anthropology:acl:read',
        'analysis_join:acl:read',
        'analysis_join:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:analysis_join:create',
    ])]
    private Site $subject;

    public function getSubject(): ?Site
    {
        return $this->subject;
    }

    public function setSubject(?Site $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
