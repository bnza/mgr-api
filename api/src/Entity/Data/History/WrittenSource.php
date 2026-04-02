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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\DynamicCollectionOrderFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\WrittenSourceCentury;
use App\Entity\Vocabulary\History\Author;
use App\Entity\Vocabulary\History\WrittenSourceType;
use App\Repository\HistoryWrittenSourceRepository;
use App\Util\EntityOneToManyRelationshipSynchronizer;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HistoryWrittenSourceRepository::class)]
#[ORM\Table(
    name: 'history_written_sources'
)]
#[ApiResource(
    shortName: 'HistoryWrittenSource',
    operations: [
        new Get(
            uriTemplate: '/data/history/written_sources/{id}',
        ),
        new GetCollection(
            uriTemplate: '/data/history/written_sources',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new Post(
            uriTemplate: '/data/history/written_sources',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:history_written_source:create']],
        ),
        new Patch(
            uriTemplate: '/data/history/written_sources/{id}',
            security: 'is_granted("update", object)',
        ),
        new Delete(
            uriTemplate: '/data/history/written_sources/{id}',
            security: 'is_granted("delete", object)',
            validationContext: ['groups' => ['validation:written_sources:delete']],
            validate: true
        ),
    ],
    normalizationContext: ['groups' => ['history_written_source:acl:read']],
    denormalizationContext: ['groups' => ['history_written_source:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'author.value',
        'subTitle',
        'title',
        'writtenSourceType.value',
        'publicationsDetails',
    ])]
#[ApiFilter(
    DynamicCollectionOrderFilter::class,
    properties: [
        'centuries.century.chronologyLower' => [
            'centuries.century.chronologyLower',
            'centuries.century.chronologyUpper',
        ],
        'centuries.century.chronologyUpper' => [
            'centuries.century.chronologyLower',
            'centuries.century.chronologyUpper',
        ],
    ])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'author' => 'exact',
        'writtenSourceType' => 'exact',
        'centuries.century' => 'exact',
        'citedWorks.citedWork' => 'exact',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'subtitle',
        'notes',
    ])]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'notes',
        'title',
        'subtitle',
        'publicationDetails',
        'author.value',
        'search' => ['title', 'author.value', 'author.variant'],
    ]
)]
#[AppAssert\NotReferenced(self::class, message: 'Cannot delete the written source because it is referenced by: {{ classes }}.', groups: ['validation:written_sources:delete'])]
class WrittenSource
{
    #[ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)]
    #[SequenceGenerator(sequenceName: 'history_cit_item_id_seq')]
    #[Groups([
        'history_written_source:acl:read',
        'history_written_source:export',
    ])]
    private int $id;

    //    #[ORM\ManyToOne(targetEntity: Language::class)]
    //    #[ORM\JoinColumn(name: 'age_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    //    #[Groups([
    //        'history_written_source:acl:read',
    //        'history_written_source:export',
    //        'history_written_source:create',
    //    ])]
    //    #[ApiProperty(required: true)]
    //    private Language $language;

    #[ORM\ManyToOne(targetEntity: WrittenSourceType::class)]
    #[ORM\JoinColumn(name: 'written_source_type_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_written_source:acl:read',
        'history_written_source:export',
        'history_written_source:create',
        'history_written_sources_cited_works:acl:read',
        'history_written_sources_cited_works:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_written_source:create',
    ])]
    #[ApiProperty(required: true)]
    private WrittenSourceType $writtenSourceType;

    #[ORM\ManyToOne(targetEntity: Author::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_written_source:acl:read',
        'history_written_source:export',
        'history_written_source:create',
        'history_written_sources_cited_works:acl:read',
        'history_written_sources_cited_works:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_written_source:create',
    ])]
    #[ApiProperty(required: true)]
    private Author $author;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'history_written_source:acl:read',
        'history_written_source:export',
        'history_written_source:create',
        'history_written_sources_cited_works:acl:read',
        'history_written_sources_cited_works:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_written_source:create',
    ])]
    #[ApiProperty(required: true)]
    private string $title;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'history_written_source:acl:read',
        'history_written_source:export',
        'history_written_source:create',
        'history_written_sources_cited_works:export',
    ])]
    private ?string $subtitle;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'history_written_source:acl:read',
        'history_written_source:export',
        'history_written_source:create',
        'history_written_sources_cited_works:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_written_source:create',
    ])]
    #[ApiProperty(required: true)]
    private string $publicationDetails;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'history_written_source:acl:read',
        'history_written_source:export',
        'history_written_source:create',
        'history_written_sources_cited_works:export',
    ])]
    private ?string $notes;

    /** @var Collection<WrittenSourceCentury> */
    #[ORM\OneToMany(
        targetEntity: WrittenSourceCentury::class,
        mappedBy: 'writtenSource',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $centuries;

    /** @var Collection<WrittenSourceCitedWork> */
    #[ORM\OneToMany(
        targetEntity: WrittenSourceCitedWork::class,
        mappedBy: 'writtenSource',
    )]
    private Collection $citedWorks;

    private EntityOneToManyRelationshipSynchronizer $centuriesSynchronizer;

    //    #[ORM\ManyToOne(targetEntity: User::class)]
    //    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    //    #[Groups([
    //        'history_written_source:acl:read',
    //    ])]
    //    private User $createdBy;

    public function __construct()
    {
        $this->centuries = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWrittenSourceType(): WrittenSourceType
    {
        return $this->writtenSourceType;
    }

    public function setWrittenSourceType(WrittenSourceType $writtenSourceType): WrittenSource
    {
        $this->writtenSourceType = $writtenSourceType;

        return $this;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): WrittenSource
    {
        $this->author = $author;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): WrittenSource
    {
        $this->title = $title;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): WrittenSource
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getPublicationDetails(): string
    {
        return $this->publicationDetails;
    }

    public function setPublicationDetails(string $publicationDetails): WrittenSource
    {
        $this->publicationDetails = $publicationDetails;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): WrittenSource
    {
        $this->notes = $notes;

        return $this;
    }

    private function getCenturiesSynchronizer(): EntityOneToManyRelationshipSynchronizer
    {
        if (!isset($this->getCenturiesSynchronizer)) {
            $this->centuriesSynchronizer = new EntityOneToManyRelationshipSynchronizer(
                $this->centuries,
                WrittenSourceCentury::class,
                'writtenSource',
                'century'
            );
        }

        return $this->centuriesSynchronizer;
    }

    #[Groups([
        'history_written_source:acl:read',
    ])]
    public function getCenturies(): Collection
    {
        return $this->centuries->map(function ($century) {
            return $century->getCentury();
        });
    }

    #[Groups([
        'history_written_source:create',
    ])]
    public function setCenturies(array|Collection $centuries): WrittenSource
    {
        if ($centuries instanceof Collection) {
            $this->centuries = $centuries;

            return $this;
        }

        $this->getCenturiesSynchronizer()->synchronize($centuries, $this);

        return $this;
    }

    #[Groups([
        'history_written_source:export',
        'history_written_sources_cited_works:export',
    ])]
    public function getCentury(): string
    {
        return implode('-', $this->centuries->map(function ($century) {
            return $century->getCentury()->value;
        })->toArray());
    }
}
