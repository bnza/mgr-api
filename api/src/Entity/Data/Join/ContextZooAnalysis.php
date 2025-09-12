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
use App\Entity\Data\Context;
use App\Entity\Data\MediaObject;
use App\Entity\Vocabulary\Analysis\Type as AnalysisType;
use App\Util\EntityOneToManyRelationshipSynchronizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'context_zoo_analyses',
)]
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
                    toProperty: 'item',
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
        'groups' => ['context_zoo_analysis:acl:read', 'context:acl:read', 'media_object_join:read'],
    ],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'item.site.code', 'item.name', 'type.value', 'document.mimeType', 'rawData.mimeType', 'context.type.value']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'item.site' => 'exact',
        'item.type' => 'exact',
        'item.contextStratigraphicUnits.stratigraphicUnit' => 'exact',
        'item.contextStratigraphicUnits.stratigraphicUnit.year' => 'exact',
        'item.contextStratigraphicUnits.stratigraphicUnit.number' => 'exact',
        'type' => 'exact',
        'document.mimeType' => 'ipartial',
        'rawData.mimeType' => 'ipartial',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'item.contextStratigraphicUnits.stratigraphicUnit.year',
        'item.contextStratigraphicUnits.stratigraphicUnit.number',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'summary',
        'contextStratigraphicUnits.stratigraphicUnit.description',
        'item.description',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'summary',
        'item.name',
        'item.description',
        'contextStratigraphicUnits.stratigraphicUnit.interpretation',
        'contextStratigraphicUnits.stratigraphicUnit.description',
    ]
)]
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
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    private ?Context $item = null;

    #[ORM\ManyToOne(targetEntity: AnalysisType::class)]
    #[ORM\JoinColumn(name: 'analysis_type_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    private AnalysisType $type;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: true)]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    private ?MediaObject $document = null;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(name: 'raw_data_id', referencedColumnName: 'id', nullable: true)]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    private ?MediaObject $rawData = null;

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

    public function getItem(): ?Context
    {
        return $this->item;
    }

    public function setItem(?Context $item): ContextZooAnalysis
    {
        $this->item = $item;

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

    public function getDocument(): ?MediaObject
    {
        return $this->document;
    }

    public function setDocument(?MediaObject $document): ContextZooAnalysis
    {
        $this->document = $document;

        return $this;
    }

    public function getRawData(): ?MediaObject
    {
        return $this->rawData;
    }

    public function setRawData(?MediaObject $rawData): ContextZooAnalysis
    {
        $this->rawData = $rawData;

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
