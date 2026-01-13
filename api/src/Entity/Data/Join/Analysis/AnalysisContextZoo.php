<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Analysis;
use App\Entity\Data\Context;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use App\Metadata\Attribute\SubResourceFilters\ApiStratigraphicUnitSubresourceFilters;
use App\Util\EntityOneToManyRelationshipSynchronizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'context_zoo_analyses',
)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'analysis',
        inversedBy: 'subjectContextZoo'
    ),
])]
#[ApiAnalysisJoinResource(
    subjectClass: Context::class,
    templateParentResourceName: 'zoo',
    itemNormalizationGroups: ['context:acl:read', 'context_zoo_analysis:acl:read'],
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
        'taxonomies.taxonomy' => 'exact',
        'taxonomies.taxonomy.family' => 'exact',
        'taxonomies.taxonomy.class' => 'exact',
        'taxonomies.taxonomy.vernacularName' => 'ipartial',
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
    ExistsFilter::class,
    properties: [
        'subject.description',
        'taxonomies',
        'taxonomies.taxonomy.family',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'subject.name',
        'subject.description',
    ]
)]
#[ApiStratigraphicUnitSubresourceFilters('subject.contextStratigraphicUnits.stratigraphicUnit')]
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
        'context_zoo_analysis:export',
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
        'analysis_join:create',
        'analysis_join:update',
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

    public static function getPermittedAnalysisTypes(): array
    {
        return array_keys(
            array_filter(
                Analysis::TYPES,
                fn ($type) => in_array($type, [Analysis::TYPE_ZOO]),
                ARRAY_FILTER_USE_KEY
            )
        );
    }
}
