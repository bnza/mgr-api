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
    name: 'analyses_context_zoo',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Context::class,
    templateParentResourceName: 'zoo',
    itemNormalizationGroups: ['context:acl:read', 'context_zoo_analysis:acl:read'],
    templateParentCategoryName: 'contexts'
)
]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'subject.site.code', 'subject.name', 'type.value', 'analysis.type.value', 'context.type.value']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject.site' => 'exact',
        'subject.type' => 'exact',
        'subject.contextStratigraphicUnits.stratigraphicUnit' => 'exact',
        'subject.contextStratigraphicUnits.stratigraphicUnit.year' => 'exact',
        'subject.contextStratigraphicUnits.stratigraphicUnit.number' => 'exact',
        'type' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'subject.contextStratigraphicUnits.stratigraphicUnit.year',
        'subject.contextStratigraphicUnits.stratigraphicUnit.number',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'summary',
        'contextStratigraphicUnits.stratigraphicUnit.description',
        'subject.description',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'summary',
        'subject.name',
        'subject.description',
        'subject.contextStratigraphicUnits.stratigraphicUnit.interpretation',
        'subject.contextStratigraphicUnits.stratigraphicUnit.description',
    ]
)]
class AnalysisContextZoo extends BaseAnalysisJoin
{

    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'analysis_join_id_seq')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Context::class, inversedBy: 'zooAnalyses')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'analysis_join:acl:read',
        'analysis_join:create',
        'context_zoo_analysis:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:analysis_join:create'])]
    private ?Context $subject = null;


    /** @var Collection<AnalysisContextZooTaxonomy> */
    #[ORM\OneToMany(
        targetEntity: AnalysisContextZooTaxonomy::class,
        mappedBy: 'analysis',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[Groups([
        'analysis_join:acl:read',
        'context_zoo_analysis:acl:read',
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
        return $this->taxonomies->map(function (/* @var AnalysisContextZooTaxonomy $item */ $item) {
            return $item->getTaxonomy();
        });
    }

    private function getTaxonomiesSynchronizer(): EntityOneToManyRelationshipSynchronizer
    {
        if (!isset($this->taxonomiesSynchronizer)) {
            $this->taxonomiesSynchronizer = new EntityOneToManyRelationshipSynchronizer(
                $this->taxonomies,
                AnalysisContextZooTaxonomy::class,
                'analysis',
                'taxonomy',
            );
        }

        return $this->taxonomiesSynchronizer;
    }

    public function setTaxonomies(array|Collection $culturalContexts): AnalysisContextZoo
    {
        if ($culturalContexts instanceof Collection) {
            $this->taxonomies = $culturalContexts;

            return $this;
        }

        $this->getTaxonomiesSynchronizer()->synchronize($culturalContexts, $this);

        return $this;
    }
}
