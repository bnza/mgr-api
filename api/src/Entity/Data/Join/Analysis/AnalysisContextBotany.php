<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Context;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use App\Util\EntityOneToManyRelationshipSynchronizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'context_botany_analyses',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Context::class,
    templateParentResourceName: 'botany',
    itemNormalizationGroups: ['context:acl:read', 'context_botany_analysis:acl:read'],
    templateParentCategoryName: 'contexts'
)
]
#[ApiFilter(
    OrderFilter::class,
    properties: ['subject.site.code', 'subject.name']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject.site' => 'exact',
        'subject.type' => 'exact',
        'subject.contextStratigraphicUnits.stratigraphicUnit' => 'exact',
        'subject.contextStratigraphicUnits.stratigraphicUnit.year' => 'exact',
        'subject.contextStratigraphicUnits.stratigraphicUnit.number' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'subject.year',
        'subject.number',
        'subject.contextStratigraphicUnits.stratigraphicUnit.year',
        'subject.contextStratigraphicUnits.stratigraphicUnit.number',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'subject.contextStratigraphicUnits.stratigraphicUnit.description',
        'subject.description',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'subject.name',
        'subject.description',
        'subject.contextStratigraphicUnits.stratigraphicUnit.interpretation',
        'subject.contextStratigraphicUnits.stratigraphicUnit.description',
    ]
)]
class AnalysisContextBotany extends BaseAnalysisJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'analysis_join_id_seq')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Context::class, inversedBy: 'botanyAnalyses')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'analysis_join:acl:read',
        'analysis_join:create',
        'context_botany_analysis:acl:read',
        'context_botany_analysis:export',
    ])]
    #[Assert\NotBlank(groups: ['validation:analysis_join:create'])]
    private ?Context $subject = null;

    /** @var Collection<AnalysisContextBotanyTaxonomy> */
    #[ORM\OneToMany(
        targetEntity: AnalysisContextBotanyTaxonomy::class,
        mappedBy: 'analysis',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[Groups([
        'analysis_join:acl:read',
        'analysis_join:create',
        'analysis_join:update',
        'context_botany_analysis:acl:read',
    ])]
    private Collection $taxonomies;

    private EntityOneToManyRelationshipSynchronizer $taxonomiesSynchronizer;

    public function __construct()
    {
        $this->taxonomies = new ArrayCollection();
    }

    public function getSubject(): ?Context
    {
        return $this->subject;
    }

    public function setSubject(?Context $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getTaxonomies(): Collection
    {
        return $this->taxonomies->map(function (/* @var AnalysisContextBotanyTaxonomy $item */ $item) {
            return $item->getTaxonomy();
        });
    }

    private function getTaxonomiesSynchronizer(): EntityOneToManyRelationshipSynchronizer
    {
        if (!isset($this->taxonomiesSynchronizer)) {
            $this->taxonomiesSynchronizer = new EntityOneToManyRelationshipSynchronizer(
                $this->taxonomies,
                AnalysisContextBotanyTaxonomy::class,
                'analysis',
                'taxonomy',
            );
        }

        return $this->taxonomiesSynchronizer;
    }

    public function setTaxonomies(array|Collection $culturalContexts): AnalysisContextBotany
    {
        if ($culturalContexts instanceof Collection) {
            $this->taxonomies = $culturalContexts;

            return $this;
        }

        $this->getTaxonomiesSynchronizer()->synchronize($culturalContexts, $this);

        return $this;
    }
}
