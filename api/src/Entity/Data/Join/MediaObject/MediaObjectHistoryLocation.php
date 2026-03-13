<?php

namespace App\Entity\Data\Join\MediaObject;

use App\Entity\Vocabulary\History\Location;
use App\Metadata\Attribute\ApiMediaObjectJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'media_object_history_location',
)]
#[ApiMediaObjectJoinResource(
    itemClass: Location::class,
    templateParentResourceName: 'history/locations',
    itemNormalizationGroups: ['Location:acl:read']
)]
class MediaObjectHistoryLocation extends BaseMediaObjectJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'media_object_join_id_seq')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'mediaObjects')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['media_object_join:acl:read', 'media_object_join:create'])]
    private Location $item;

    public function getItem(): Location
    {
        return $this->item;
    }

    public function setItem(Location $item): BaseMediaObjectJoin
    {
        $this->item = $item;

        return $this;
    }
}
