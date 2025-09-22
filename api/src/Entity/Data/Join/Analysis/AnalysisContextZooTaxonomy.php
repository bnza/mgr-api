<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Vocabulary\Zoo\Taxonomy;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'analysis_context_zoo_taxonomies',
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    routePrefix: 'data',
)]
#[ORM\UniqueConstraint(columns: ['analysis_id', 'taxonomy_id'])]
class AnalysisContextZooTaxonomy
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    private int $id;

    #[ORM\ManyToOne(targetEntity: AnalysisContextZoo::class, inversedBy: 'taxonomies')]
    #[ORM\JoinColumn(name: 'analysis_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AnalysisContextZoo $analysis;
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

    public function getAnalysis(): AnalysisContextZoo
    {
        return $this->analysis;
    }

    public function setAnalysis(AnalysisContextZoo $analysis): AnalysisContextZooTaxonomy
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function getTaxonomy(): Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(Taxonomy $taxonomy): AnalysisContextZooTaxonomy
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }
}
