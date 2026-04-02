<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Data\History\WrittenSource;
use App\Entity\Vocabulary\Century;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_written_source_centuries',
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    routePrefix: 'data',
    order: ['id' => 'DESC'],
)]
#[ORM\UniqueConstraint(columns: ['written_source_id', 'century_id'])]
class WrittenSourceCentury
{
    #[ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WrittenSource::class, inversedBy: 'centuries')]
    #[ORM\JoinColumn(name: 'written_source_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WrittenSource $writtenSource;

    #[ORM\ManyToOne(targetEntity: Century::class)]
    #[ORM\JoinColumn(name: 'century_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_written_source:acl:read',
        'history_written_source:export',
    ])]
    private Century $century;

    public function getId(): int
    {
        return $this->id;
    }

    public function getWrittenSource(): WrittenSource
    {
        return $this->writtenSource;
    }

    public function setWrittenSource(WrittenSource $writtenSource): WrittenSourceCentury
    {
        $this->writtenSource = $writtenSource;

        return $this;
    }

    public function getCentury(): Century
    {
        return $this->century;
    }

    public function setCentury(Century $century): WrittenSourceCentury
    {
        $this->century = $century;

        return $this;
    }
}
