<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Analysis;
use App\Entity\Data\Context;
use App\Entity\Vocabulary\Analysis\Type as AnalysisType;
use App\Util\EntityOneToManyRelationshipSynchronizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'context_zoo_analyses',
)]
#[ORM\UniqueConstraint(columns: ['subject_id', 'analysis_id'])]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/analyses/contexts/zoo/{id}',
        ),
        new GetCollection('/analyses/contexts/zoo'),
        new GetCollection(
            uriTemplate: '/contexts/{parentId}/analyses/zoo',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'subject',
                    fromClass: Context::class,
                ),
            ],
        ),
        new Post(
            uriTemplate: '/analyses/contexts/zoo',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:context_zoo_analyses:create']],
        ),
        new Patch(
            uriTemplate: '/analyses/contexts/zoo/{id}',
            security: 'is_granted("update", object)',
        ),
        new Delete(
            uriTemplate: '/analyses/contexts/zoo/{id}',
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: [
        'groups' => ['context_zoo_analysis:acl:read', 'context:acl:read', 'analysis:acl:read'],
    ],
)]
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
#[UniqueEntity(
    fields: ['subject', 'analysis'],
    message: 'Duplicate [subject, analysis] combination.',
    groups: ['validation:context_zoo_analysis:create'])
]
class ContextZooAnalysis
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Context::class, inversedBy: 'zooAnalyses')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:context_zoo_analysis:create'])]
    private ?Context $subject = null;

    #[ORM\ManyToOne(targetEntity: Analysis::class)]
    #[ORM\JoinColumn(name: 'analysis_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:context_zoo_analysis:create'])]
    private ?Analysis $analysis = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    private ?string $summary = null;

    /** @var Collection<SiteCulturalContext> */
    #[ORM\OneToMany(
        targetEntity: ContextZooAnalysisTaxonomy::class,
        mappedBy: 'analysis',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    private Collection $taxonomies;

    private EntityOneToManyRelationshipSynchronizer $taxonomiesSynchronizer;

    public function __construct()
    {
        $this->taxonomies = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ContextZooAnalysis
    {
        $this->id = $id;

        return $this;
    }

    public function getSubject(): ?Context
    {
        return $this->subject;
    }

    public function setSubject(?Context $subject): ContextZooAnalysis
    {
        $this->subject = $subject;

        return $this;
    }

    public function getType(): AnalysisType
    {
        return $this->type;
    }

    public function setType(AnalysisType $type): ContextZooAnalysis
    {
        $this->type = $type;

        return $this;
    }

    public function getAnalysis(): ?Analysis
    {
        return $this->analysis;
    }

    public function setAnalysis(?Analysis $analysis): ContextZooAnalysis
    {
        $this->analysis = $analysis;
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): ContextZooAnalysis
    {
        $this->summary = $summary;

        return $this;
    }

    public function getTaxonomies(): Collection
    {
        return $this->taxonomies->map(function (/* @var ContextZooAnalysisTaxonomy $item */ $item) {
            return $item->getTaxonomy();
        });
    }

    private function getTaxonomiesSynchronizer(): EntityOneToManyRelationshipSynchronizer
    {
        if (!isset($this->taxonomiesSynchronizer)) {
            $this->taxonomiesSynchronizer = new EntityOneToManyRelationshipSynchronizer(
                $this->taxonomies,
                ContextZooAnalysisTaxonomy::class,
                'analysis',
                'taxonomy',
            );
        }

        return $this->taxonomiesSynchronizer;
    }

    public function setTaxonomies(array|Collection $culturalContexts): ContextZooAnalysis
    {
        if ($culturalContexts instanceof Collection) {
            $this->taxonomies = $culturalContexts;

            return $this;
        }

        $this->getTaxonomiesSynchronizer()->synchronize($culturalContexts, $this);

        return $this;
    }
}
