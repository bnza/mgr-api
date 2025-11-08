<?php

namespace App\Entity\Data\Join\Analysis\AbsDating;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Data\Join\Analysis\AnalysisZooTooth;
use App\Entity\Data\Join\Analysis\BaseAnalysisJoin;
use App\Metadata\Attribute\ApiAbsDatingAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'abs_dating_analysis_zoo_teeth',
)]
#[ApiAbsDatingAnalysisJoinResource(
    subjectClass: self::class,
    templateParentResourceName: 'zoo/teeth',
    itemNormalizationGroups: ['abs_dating_analysis_join:acl:read', 'analysis_zoo_tooth:acl:read']
)]
class AbsDatingAnalysisZooTooth extends AbsDatingAnalysisJoin
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: AnalysisZooTooth::class, inversedBy: 'absDatingAnalysis')]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ApiProperty(identifier: false)]
    #[Groups(['abs_dating_analysis_join:acl:read', 'abs_dating_analysis_join:create'])]
    protected BaseAnalysisJoin $analysis;
}
