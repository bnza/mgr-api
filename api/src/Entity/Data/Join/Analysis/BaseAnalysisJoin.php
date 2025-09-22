<?php

namespace App\Entity\Data\Join\Analysis;

use App\Entity\Data\Analysis;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @template T of object
 */
#[ORM\MappedSuperclass]
#[ORM\UniqueConstraint(columns: ['subject_id', 'analysis_id'])]
#[UniqueEntity(
    fields: ['subject', 'analysis'],
    message: 'Duplicate [subject, analysis] combination.',
    groups: ['validation:analysis_join:create'])
]
abstract class BaseAnalysisJoin
{
    // You must define #[ORM\Id],  #[ORM\GeneratedValue] and #[ORM\Column] in the subclass to share the same generator
    // For serialization contexts @see MediaObjectJoinApiResource::class
    #[Groups(['analysis_join:acl:read', 'analysis_join:create'])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Analysis::class)]
    #[ORM\JoinColumn(name: 'analysis_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['analysis_join:acl:read', 'analysis_join:create'])]
    #[Assert\NotBlank(groups: ['validation:analysis_join:create'])]
    protected Analysis $analysis;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['analysis_join:acl:read', 'analysis_join:create'])]
    protected ?string $summary = null;

    /** @return T */
    abstract public function getSubject(): ?object;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getAnalysis(): Analysis
    {
        return $this->analysis;
    }

    public function setAnalysis(Analysis $analysis): self
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): BaseAnalysisJoin
    {
        $this->summary = $summary;

        return $this;
    }
}
