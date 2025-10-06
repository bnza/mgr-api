<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Vocabulary\Botany\Taxonomy;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'analysis_context_botany_taxonomies',
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    routePrefix: 'data',
)]
#[ORM\UniqueConstraint(columns: ['analysis_id', 'taxonomy_id'])]
class AnalysisContextBotanyTaxonomy
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    private int $id;

    #[ORM\ManyToOne(targetEntity: AnalysisContextBotany::class, inversedBy: 'taxonomies')]
    #[ORM\JoinColumn(name: 'analysis_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AnalysisContextBotany $analysis;
    #[ORM\ManyToOne(targetEntity: Taxonomy::class)]
    #[ORM\JoinColumn(name: 'taxonomy_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_botany_analysis:acl:read',
    ])]
    private Taxonomy $taxonomy;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnalysis(): AnalysisContextBotany
    {
        return $this->analysis;
    }

    public function setAnalysis(AnalysisContextBotany $analysis): AnalysisContextBotanyTaxonomy
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function getTaxonomy(): Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(Taxonomy $taxonomy): AnalysisContextBotanyTaxonomy
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }
}
