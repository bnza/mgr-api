<?php

namespace App\Entity\Data\View;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Entity\Data\Analysis;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'vw_abs_dating_analyses',
)]
#[ApiResource(
    shortName: 'AbsDatingAnalysis',
    operations: [
        new Get(
            uriTemplate: '/analyses/absolute_dating/{id}',
            requirements: ['id' => '\d+']
        ),
        new GetCollection(
            uriTemplate: '/analyses/absolute_dating'
        ),
        new GetCollection(
            uriTemplate: '/analyses/{parentId}/absolute_dating',
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
        'groups' => ['abs_dating_analysis:read'],
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
        'datingLower',
        'datingUpper',
        'uncalibratedDating',
        'error',
        'calibrationCurve',
        'stratigraphicUnit.site.code',
    ]
)]
class AbsDatingAnalysis
{
    #[
        ORM\Id,
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups(['abs_dating_analysis:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class)]
    #[Groups(['abs_dating_analysis:read'])]
    private StratigraphicUnit $stratigraphicUnit;

    #[ORM\ManyToOne(targetEntity: Analysis::class)]
    #[Groups(['abs_dating_analysis:read'])]
    private Analysis $analysis;

    #[ORM\Column(type: 'bigint')]
    #[Groups(['abs_dating_analysis:read'])]
    private int $subjectId;

    #[ORM\Column(type: 'string')]
    #[Groups(['abs_dating_analysis:read'])]
    private string $subjectType;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['abs_dating_analysis:read'])]
    protected int $datingLower;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['abs_dating_analysis:read'])]
    protected int $datingUpper;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['abs_dating_analysis:read'])]
    protected int $uncalibratedDating;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['abs_dating_analysis:read'])]
    protected int $error;

    #[ORM\Column(type: 'string')]
    #[Groups(['abs_dating_analysis:read'])]
    protected string $calibrationCurve;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['abs_dating_analysis:read'])]
    protected string $notes;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnalysis(): Analysis
    {
        return $this->analysis;
    }

    public function setAnalysis(Analysis $analysis): AbsDatingAnalysis
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function getSubjectId(): int
    {
        return $this->subjectId;
    }

    public function setSubjectId(int $subjectId): AbsDatingAnalysis
    {
        $this->subjectId = $subjectId;

        return $this;
    }

    public function getSubjectType(): string
    {
        return $this->subjectType;
    }

    public function setSubjectType(string $subjectType): AbsDatingAnalysis
    {
        $this->subjectType = $subjectType;

        return $this;
    }

    public function getStratigraphicUnit(): StratigraphicUnit
    {
        return $this->stratigraphicUnit;
    }

    public function setStratigraphicUnit(StratigraphicUnit $stratigraphicUnit): AbsDatingAnalysis
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    public function getDatingLower(): int
    {
        return $this->datingLower;
    }

    public function setDatingLower(int $datingLower): AbsDatingAnalysis
    {
        $this->datingLower = $datingLower;

        return $this;
    }

    public function getDatingUpper(): int
    {
        return $this->datingUpper;
    }

    public function setDatingUpper(int $datingUpper): AbsDatingAnalysis
    {
        $this->datingUpper = $datingUpper;

        return $this;
    }

    public function getUncalibratedDating(): int
    {
        return $this->uncalibratedDating;
    }

    public function setUncalibratedDating(int $uncalibratedDating): AbsDatingAnalysis
    {
        $this->uncalibratedDating = $uncalibratedDating;

        return $this;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function setError(int $error): AbsDatingAnalysis
    {
        $this->error = $error;

        return $this;
    }

    public function getCalibrationCurve(): string
    {
        return $this->calibrationCurve;
    }

    public function setCalibrationCurve(string $calibrationCurve): AbsDatingAnalysis
    {
        $this->calibrationCurve = $calibrationCurve;

        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): AbsDatingAnalysis
    {
        $this->notes = $notes;

        return $this;
    }
}
