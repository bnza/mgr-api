<?php

namespace App\Entity\Data\View;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Entity\Data\Analysis;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'vw_analysis_subjects',
)]
#[ApiResource(
    shortName: 'AnalysisSubject',
    operations: [
        new Get(),
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/analyses/{parentId}/subjects',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'analysis',
                    fromClass: Analysis::class,
                ),
            ],
            requirements: ['parentId' => '\d+'],
        ),
    ],
    routePrefix: 'data',
    normalizationContext: [
        'groups' => ['analysis_subject:read'],
    ]
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'analysis.identifier',
        'analysis.laboratory',
        'analysis.responsible',
        'analysis.type.value',
        'analysis.year',
    ]
)]
class AnalysisSubjectView
{
    #[
        ORM\Id,
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups(['analysis_subject:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Analysis::class)]
    #[Groups(['analysis_subject:read'])]
    private Analysis $analysis;

    #[ORM\Column(type: 'bigint')]
    #[Groups(['analysis_subject:read'])]
    private int $subjectId;

    #[ORM\Column(type: 'string')]
    #[Groups(['analysis_subject:read'])]
    private string $joinResourceName;

    #[ORM\Column(type: 'string')]
    #[Groups(['analysis_subject:read'])]
    private string $resourceName;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnalysis(): Analysis
    {
        return $this->analysis;
    }

    public function setAnalysis(Analysis $analysis): AnalysisSubjectView
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function getSubjectId(): int
    {
        return $this->subjectId;
    }

    public function setSubjectId(int $subjectId): AnalysisSubjectView
    {
        $this->subjectId = $subjectId;

        return $this;
    }

    public function getJoinResourceName(): string
    {
        return $this->joinResourceName;
    }

    public function setJoinResourceName(string $joinResourceName): AnalysisSubjectView
    {
        $this->joinResourceName = $joinResourceName;

        return $this;
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function setResourceName(string $resourceName): AnalysisSubjectView
    {
        $this->resourceName = $resourceName;

        return $this;
    }
}
