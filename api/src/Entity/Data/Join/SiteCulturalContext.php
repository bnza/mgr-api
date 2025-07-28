<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Data\Site;
use App\Entity\Vocabulary\CulturalContext;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'site_cultural_contexts',
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ])]
class SiteCulturalContext
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: 'culturalContexts')]
    #[ORM\JoinColumn(name: 'site_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Site $site;

    #[ORM\ManyToOne(targetEntity: CulturalContext::class)]
    #[ORM\JoinColumn(name: 'cultural_context_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'site:acl:read',
    ])]
    private CulturalContext $culturalContext;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): SiteCulturalContext
    {
        $this->site = $site;

        return $this;
    }

    public function getCulturalContext(): CulturalContext
    {
        return $this->culturalContext;
    }

    public function setCulturalContext(CulturalContext $culturalContext): SiteCulturalContext
    {
        $this->culturalContext = $culturalContext;

        return $this;
    }
}
