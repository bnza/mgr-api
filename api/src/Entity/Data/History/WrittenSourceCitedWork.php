<?php

namespace App\Entity\Data\History;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\OptionalUpperLimitRangeOverlapFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Vocabulary\History\CitedWork;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_written_sources_cited_works',
)]
#[ApiResource(
    shortName: 'HistoryWrittenSourceCitedWork',
    operations: [
        new Get(
            uriTemplate: '/data/history/written_sources_cited_works/{id}',
        ),
        new GetCollection(
            uriTemplate: '/data/history/written_sources_cited_works',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new GetCollection(
            uriTemplate: '/data/history/written_sources/{parentId}/cited_works',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'writtenSource',
                    fromClass: WrittenSource::class,
                ),
            ]
        ),
        new Post(
            uriTemplate: '/data/history/written_sources_cited_works',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:history_written_sources_cited_works:create']],
        ),
        new Patch(
            uriTemplate: '/data/history/written_sources_cited_works/{id}',
            security: 'is_granted("update", object)',
        ),
        new Delete(
            uriTemplate: '/data/history/written_sources_cited_works/{id}',
            security: 'is_granted("delete", object)',
        ),
    ],
    normalizationContext: ['groups' => ['history_written_sources_cited_works:acl:read']],
    denormalizationContext: ['groups' => ['history_written_sources_cited_works:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'writtenSource.author.value',
        'writtenSource.title',
        'citedWork.value',
        'yearCompleted',
        'yearCompletedUpper',
    ])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'writtenSource' => 'exact',
        'citedWork' => 'exact',
        'writtenSource.author' => 'exact',
        'writtenSource.centuries.century' => 'exact',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'writtenSource.title',
        'writtenSource.subtitle',
        'writtenSource.publicationDetails',
        'search' => ['writtenSource.title', 'writtenSource.author.value', 'writtenSource.author.variant'],
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'writtenSource.title',
        'writtenSource.subtitle',
        'writtenSource.publicationDetails',
        'search' => ['writtenSource.title', 'writtenSource.author.value', 'writtenSource.author.variant'],
    ]
)]
#[ApiFilter(
    OptionalUpperLimitRangeOverlapFilter::class,
    properties: [
        'yearCompleted' => [
            'lowerProperty' => 'yearCompleted',
            'upperProperty' => 'yearCompletedUpper',
        ],
    ]
)]
class WrittenSourceCitedWork
{
    #[ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)]
    #[SequenceGenerator(sequenceName: 'history_cit_item_id_seq')]
    #[Groups([
        'history_written_sources_cited_works:acl:read',
        'history_written_sources_cited_works:export',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WrittenSource::class)]
    #[ORM\JoinColumn(name: 'written_source_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_written_sources_cited_works:acl:read',
        'history_written_sources_cited_works:export',
        'history_written_sources_cited_works:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_written_sources_cited_works:create',
    ])]
    #[ApiProperty(required: true)]
    private WrittenSource $writtenSource;

    #[ORM\ManyToOne(targetEntity: CitedWork::class)]
    #[ORM\JoinColumn(name: 'cited_work_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_written_sources_cited_works:acl:read',
        'history_written_sources_cited_works:export',
        'history_written_sources_cited_works:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_written_sources_cited_works:create',
    ])]
    #[ApiProperty(required: true)]
    private CitedWork $citedWork;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'history_written_sources_cited_works:create',
        'history_written_sources_cited_works:acl:read',
    ])]
    #[Assert\AtLeastOneOf([
        new Assert\Sequentially([
            new Assert\GreaterThanOrEqual(value: -32768),
            new AppAssert\IsLessThanOrEqualToCurrentYear(),
        ],
            groups: ['validation:history_written_sources_cited_works:create']),
    ],
        groups: ['validation:history_written_sources_cited_works:create']
    )]
    #[Assert\LessThanOrEqual(propertyPath: 'yearCompletedUpper', groups: ['validation:history_written_sources_cited_works:create'])]
    #[ApiProperty(required: true)]
    private int $yearCompleted;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'history_written_sources_cited_works:create',
        'history_written_sources_cited_works:acl:read',
    ])]
    #[Assert\AtLeastOneOf([
        new Assert\Sequentially([
            new Assert\GreaterThanOrEqual(value: -32768),
            new AppAssert\IsLessThanOrEqualToCurrentYear(),
        ],
            groups: ['validation:history_written_sources_cited_works:create']),
    ],
        groups: ['validation:history_written_sources_cited_works:create']
    )]
    #[Assert\GreaterThanOrEqual(propertyPath: 'yearCompleted', groups: ['validation:history_written_sources_cited_works:create'])]
    #[ApiProperty(required: true)]
    private ?int $yearCompletedUpper;

    public function getId(): int
    {
        return $this->id;
    }

    public function getWrittenSource(): WrittenSource
    {
        return $this->writtenSource;
    }

    public function setWrittenSource(WrittenSource $writtenSource): WrittenSourceCitedWork
    {
        $this->writtenSource = $writtenSource;

        return $this;
    }

    public function getCitedWork(): CitedWork
    {
        return $this->citedWork;
    }

    public function setCitedWork(CitedWork $citedWork): WrittenSourceCitedWork
    {
        $this->citedWork = $citedWork;

        return $this;
    }

    public function getYearCompleted(): int
    {
        return $this->yearCompleted;
    }

    public function setYearCompleted(int $yearCompleted): WrittenSourceCitedWork
    {
        $this->yearCompleted = $yearCompleted;

        return $this;
    }

    public function getYearCompletedUpper(): ?int
    {
        return $this->yearCompletedUpper;
    }

    public function setYearCompletedUpper(?int $yearCompletedUpper): WrittenSourceCitedWork
    {
        $this->yearCompletedUpper = $yearCompletedUpper;

        return $this;
    }

    #[Groups([
        'history_written_sources_cited_works:export',
    ])]
    public function getYearCompletedRange(): string
    {
        return implode('-', array_filter([$this->yearCompleted, $this->yearCompletedUpper]));
    }
}
