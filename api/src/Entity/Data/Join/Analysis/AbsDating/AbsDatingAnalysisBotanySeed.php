<?php

namespace App\Entity\Data\Join\Analysis\AbsDating;

use App\Entity\Data\Join\Analysis\AnalysisBotanySeed;
use App\Entity\Data\Join\Analysis\BaseAnalysisJoin;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'abs_dating_analysis_botany_seeds',
)]
class AbsDatingAnalysisBotanySeed extends AbsDatingAnalysisJoin
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: AnalysisBotanySeed::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected BaseAnalysisJoin $analysis;
}
