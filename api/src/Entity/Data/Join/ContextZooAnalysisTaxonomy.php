<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Vocabulary\Zoo\Taxonomy;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'context_zoo_analysis_taxonomies',
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    routePrefix: 'data',
)]
#[ORM\UniqueConstraint(columns: ['analysis_id', 'taxonomy_id'])]
class ContextZooAnalysisTaxonomy
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    private int $id;

    #[ORM\ManyToOne(targetEntity: ContextZooAnalysis::class, inversedBy: 'taxonomies')]
    #[ORM\JoinColumn(name: 'analysis_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ContextZooAnalysis $analysis;
    #[ORM\ManyToOne(targetEntity: Taxonomy::class)]
    #[ORM\JoinColumn(name: 'taxonomy_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_zoo_analysis:acl:read',
    ])]
    private Taxonomy $taxonomy;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnalysis(): ContextZooAnalysis
    {
        return $this->analysis;
    }

    public function setAnalysis(ContextZooAnalysis $analysis): ContextZooAnalysisTaxonomy
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function getTaxonomy(): Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(Taxonomy $taxonomy): ContextZooAnalysisTaxonomy
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }
}
