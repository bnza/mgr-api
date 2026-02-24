<?php

namespace App\Entity\Data\Join\MediaObject;

use App\Entity\Data\SamplingStratigraphicUnit;
use App\Metadata\Attribute\ApiMediaObjectJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'media_object_sampling_stratigraphic_units',
)]
#[ApiMediaObjectJoinResource(
    itemClass: SamplingStratigraphicUnit::class,
    templateParentResourceName: 'sampling_stratigraphic_units',
    itemNormalizationGroups: ['sampling_su:read']
)]
class MediaObjectSamplingStratigraphicUnit extends BaseMediaObjectJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'media_object_join_id_seq')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: SamplingStratigraphicUnit::class, inversedBy: 'mediaObjects')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['media_object_join:acl:read', 'media_object_join:create'])]
    private SamplingStratigraphicUnit $item;

    public function getItem(): SamplingStratigraphicUnit
    {
        return $this->item;
    }

    public function setItem(SamplingStratigraphicUnit $item): BaseMediaObjectJoin
    {
        $this->item = $item;

        return $this;
    }
}
