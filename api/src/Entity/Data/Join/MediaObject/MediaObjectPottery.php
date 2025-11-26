<?php

namespace App\Entity\Data\Join\MediaObject;

use App\Entity\Data\Pottery;
use App\Metadata\Attribute\ApiMediaObjectJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'media_object_potteries',
)]
#[ApiMediaObjectJoinResource(
    itemClass: Pottery::class,
    templateParentResourceName: 'potteries',
    itemNormalizationGroups: ['analysis:acl:read']
)]
class MediaObjectPottery extends BaseMediaObjectJoin
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'media_object_join_id_seq')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Pottery::class, inversedBy: 'mediaObjects')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['media_object_join:acl:read', 'media_object_join:create'])]
    private Pottery $item;

    public function getItem(): Pottery
    {
        return $this->item;
    }

    public function setItem(Pottery $item): BaseMediaObjectJoin
    {
        $this->item = $item;

        return $this;
    }
}
