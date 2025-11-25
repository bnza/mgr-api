<?php

namespace App\Entity\Data\Join\Analysis;

use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Entity\Data\Analysis;
use App\Entity\Data\Botany\Seed;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisBotanySeed;
use App\Entity\Data\Join\Analysis\AbsDating\AbsDatingAnalysisJoin;
use App\Metadata\Attribute\ApiAnalysisJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'analysis_botany_seeds',
)]
#[ApiAnalysisJoinResource(
    subjectClass: Seed::class,
    templateParentResourceName: 'botany/seeds',
    itemNormalizationGroups: ['botany_seed:acl:read', 'botany_seed_analysis:acl:read'])
]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'subject.stratigraphicUnit.site' => 'exact',
        'subject.stratigraphicUnit' => 'exact',
        'subject.taxonomy' => 'exact',
        'subject.element' => 'exact',
        'subject.part' => 'exact',
        'subject.taxonomy.family' => 'exact',
        'subject.taxonomy.class' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'subject.stratigraphicUnit.number',
        'subject.stratigraphicUnit.year',
    ]
)]
class AnalysisBotanySeed extends BaseAnalysisJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'analysis_join_id_seq')]
    #[Groups([
        'analysis_join:acl:read',
        'botany_seed_analysis:acl:read',
        'botany_seed_analysis:export',
    ])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Seed::class, inversedBy: 'analyses')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'botany_seed_analysis:acl:read',
        'botany_seed_analysis:export',
        'analysis_join:acl:read',
        'analysis_join:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:analysis_join:create',
    ])]
    private Seed $subject;

    #[ORM\OneToOne(
        targetEntity: AbsDatingAnalysisBotanySeed::class,
        mappedBy: 'analysis',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Groups([
        'botany_seed_analysis:acl:read',
        'botany_seed_analysis:export',
        'analysis_join:acl:read',
        'analysis_join:create',
        'analysis_join:update',
    ])]
    private ?AbsDatingAnalysisJoin $absDatingAnalysis;

    public function getSubject(): ?Seed
    {
        return $this->subject;
    }

    public function setSubject(?Seed $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getAbsDatingAnalysis(): ?AbsDatingAnalysisJoin
    {
        return $this->absDatingAnalysis;
    }

    public function setAbsDatingAnalysis(?AbsDatingAnalysisJoin $absDatingAnalysis): self
    {
        $this->absDatingAnalysis = $absDatingAnalysis;
        $absDatingAnalysis?->setAnalysis($this);

        return $this;
    }

    public static function getPermittedAnalysisTypes(): array
    {
        return array_keys(
            array_filter(
                Analysis::TYPES,
                fn ($type) => in_array(
                    $type['group'],
                    [
                        Analysis::GROUP_ABS_DATING,
                        Analysis::GROUP_MICROSCOPE,
                        Analysis::GROUP_MATERIAL_ANALYSIS,
                    ]
                )
            )
        );
    }
}
