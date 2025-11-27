<?php

namespace App\Entity\Data\Join\MediaObject;

use App\Entity\Data\MediaObject;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\MappedSuperclass]
#[ORM\UniqueConstraint(columns: ['item_id', 'media_object_id'])]
abstract class BaseMediaObjectJoin
{
    // You must define #[ORM\Id],  #[ORM\GeneratedValue] and #[ORM\Column] in the subclass to share the same generator
    // For serialization contexts @see MediaObjectJoinApiResource::class
    #[Groups(['media_object_join:acl:read', 'media_object_join:create'])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(name: 'media_object_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['media_object_join:acl:read', 'media_object_join:create'])]
    private MediaObject $mediaObject;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['media_object_join:acl:read', 'media_object_join:create'])]
    private ?string $description;

    abstract public function getItem(): object;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): BaseMediaObjectJoin
    {
        $this->id = $id;

        return $this;
    }

    public function getMediaObject(): MediaObject
    {
        return $this->mediaObject;
    }

    public function setMediaObject(MediaObject $mediaObject): BaseMediaObjectJoin
    {
        $this->mediaObject = $mediaObject;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): BaseMediaObjectJoin
    {
        $this->description = $description ?? null;

        return $this;
    }
}
