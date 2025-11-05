<?php

namespace App\Entity\Data\Join\Analysis\AbsDating;

use App\Entity\Data\Join\Analysis\BaseAnalysisJoin;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @template T of object
 */
#[ORM\MappedSuperclass]
class AbsDatingAnalysisJoin
{
    protected BaseAnalysisJoin $analysis;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['abs_dating_analysis_join:acl:read', 'abs_dating_analysis_join:create'])]
    #[Assert\NotBlank(groups: ['validation:abs_dating_analysis_join:create'])]
    protected int $datingLower;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['abs_dating_analysis_join:acl:read', 'abs_dating_analysis_join:create'])]
    #[Assert\NotBlank(groups: ['validation:abs_dating_analysis_join:create'])]
    protected int $datingUpper;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['abs_dating_analysis_join:acl:read', 'abs_dating_analysis_join:create'])]
    #[Assert\NotBlank(groups: ['validation:abs_dating_analysis_join:create'])]
    protected int $uncalibratedDating;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['abs_dating_analysis_join:acl:read', 'abs_dating_analysis_join:create'])]
    #[Assert\NotBlank(groups: ['validation:abs_dating_analysis_join:create'])]
    protected int $error;

    #[ORM\Column(type: 'string')]
    #[Groups(['abs_dating_analysis_join:acl:read', 'abs_dating_analysis_join:create'])]
    #[Assert\NotBlank(groups: ['validation:abs_dating_analysis_join:create'])]
    protected string $calibrationCurve;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['abs_dating_analysis_join:acl:read', 'abs_dating_analysis_join:create'])]
    protected string $notes;

    public function getId(): int
    {
        return $this->analysis->getId();
    }

    public function getAnalysis(): BaseAnalysisJoin
    {
        return $this->analysis;
    }

    public function setAnalysis(BaseAnalysisJoin $analysis): AbsDatingAnalysisJoin
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function getDatingLower(): int
    {
        return $this->datingLower;
    }

    public function setDatingLower(int $datingLower): AbsDatingAnalysisJoin
    {
        $this->datingLower = $datingLower;

        return $this;
    }

    public function getDatingUpper(): int
    {
        return $this->datingUpper;
    }

    public function setDatingUpper(int $datingUpper): AbsDatingAnalysisJoin
    {
        $this->datingUpper = $datingUpper;

        return $this;
    }

    public function getUncalibratedDating(): int
    {
        return $this->uncalibratedDating;
    }

    public function setUncalibratedDating(int $uncalibratedDating): AbsDatingAnalysisJoin
    {
        $this->uncalibratedDating = $uncalibratedDating;

        return $this;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function setError(int $error): AbsDatingAnalysisJoin
    {
        $this->error = $error;

        return $this;
    }

    public function getCalibrationCurve(): string
    {
        return $this->calibrationCurve;
    }

    public function setCalibrationCurve(string $calibrationCurve): AbsDatingAnalysisJoin
    {
        $this->calibrationCurve = $calibrationCurve;

        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): AbsDatingAnalysisJoin
    {
        $this->notes = $notes;

        return $this;
    }
}
