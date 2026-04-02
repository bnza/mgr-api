<?php

namespace App\Entity\Data\Join\MediaObject;

use App\Entity\Data\PaleoclimateSample;
use App\Metadata\Attribute\ApiMediaObjectJoinResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'media_object_paleoclimate_sample',
)]
#[ApiMediaObjectJoinResource(
    itemClass: PaleoclimateSample::class,
    templateParentResourceName: 'paleoclimate_samples',
    itemNormalizationGroups: ['sus:acl:read']
)]
class MediaObjectPaleoclimateSample extends BaseMediaObjectJoin
{
    #[ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)]
    #[SequenceGenerator(sequenceName: 'media_object_join_id_seq')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: PaleoclimateSample::class, inversedBy: 'mediaObjects')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['media_object_join:acl:read', 'media_object_join:create'])]
    private PaleoclimateSample $item;

    public function getItem(): PaleoclimateSample
    {
        return $this->item;
    }

    public function setItem(PaleoclimateSample $item): BaseMediaObjectJoin
    {
        $this->item = $item;

        return $this;
    }
}
