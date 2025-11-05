<?php

namespace App\Entity\Data\Join\Analysis\AbsDating;

use App\Entity\Data\Join\Analysis\AnalysisBotanyCharcoal;
use App\Entity\Data\Join\Analysis\BaseAnalysisJoin;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'abs_dating_analysis_botany_charcoals',
)]
class AbsDatingAnalysisBotanyCharcoal extends AbsDatingAnalysisJoin
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: AnalysisBotanyCharcoal::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected BaseAnalysisJoin $analysis;
}
