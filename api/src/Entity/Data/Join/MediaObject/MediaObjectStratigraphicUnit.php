<?php

namespace App\Entity\Data\Join\MediaObject;

use App\Entity\Data\StratigraphicUnit;
use App\Metadata\Attribute\ApiMediaObjectJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'media_object_stratigraphic_units',
)]
#[ApiMediaObjectJoinResource(
    itemClass: StratigraphicUnit::class,
    templateParentResourceName: 'stratigraphic_units',
    itemNormalizationGroups: ['sus:acl:read']
)]
class MediaObjectStratigraphicUnit extends BaseMediaObjectJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'media_object_join_id_seq')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['media_object_join:acl:read', 'media_object_join:create'])]
    private StratigraphicUnit $item;

    public function getItem(): StratigraphicUnit
    {
        return $this->item;
    }

    public function setItem(StratigraphicUnit $item): BaseMediaObjectJoin
    {
        $this->item = $item;

        return $this;
    }
}
