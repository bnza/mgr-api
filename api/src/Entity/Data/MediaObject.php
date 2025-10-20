<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\Entity\Auth\User;
use App\Entity\Vocabulary\MediaObject\Type;
use App\Service\MediaObjectThumbnailer;
use App\State\MediaObjectPostProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'media_objects')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(
            requirements: [
                'id' => '^\d+$',
            ],
        ),
        new Get(
            uriTemplate: '/media_objects/{sha256}',
            uriVariables: [
                'sha256',
            ],
            requirements: [
                'sha256' => '^[a-f0-9]{64}$',
            ],
        ),
        new GetCollection(),
        new Post(
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['file', 'type'],
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                    'type' => [
                                        'type' => 'string',
                                        'format' => 'iri-reference',
                                        'example' => '/api/vocabulary/media_object/types/1',
                                    ],
                                    'description' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    required: true
                )
            ),
            denormalizationContext: ['groups' => ['media_object:create']],
            securityPostDenormalize: "is_granted('create', object)",
            validationContext: ['groups' => ['validation:media_object:create']],
            processor: MediaObjectPostProcessor::class
        ),
        new Patch(
            denormalizationContext: ['groups' => ['media_object:update']],
            security: 'is_granted("update", object)',
            validationContext: ['groups' => ['validation:media_object:update']],
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['media_object:acl:read']],
    order: ['id' => 'DESC'],
)]
#[Vich\Uploadable]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'mimeType',
    'originalFilename',
    'sha256',
    'type',
    'type.group',
    'type.value',
    'size',
    'description',
    'uploadedBy.email',
    'uploadDate',
])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'sha256' => 'exact',
        'originalFilename' => 'ipartial',
        'mimeType' => 'ipartial',
        'type.group' => 'exact',
        'type' => 'exact',
        'description' => 'ipartial',
        'uploadedBy.email' => 'ipartial',
        'uploadDate' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'size',
        'uploadDate',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'description',
    ]
)]
#[UniqueEntity(
    fields: ['sha256'],
    message: 'Duplicate media.',
    ignoreNull: false,
    groups: ['validation:media_object:create']
)]
class MediaObject
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'type_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'media_object:acl:read',
        'media_object:create',
        'media_object:update',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:media_object:create',
        'validation:media_object:update',
    ])]
    private Type $type;

    #[Vich\UploadableField(
        mapping: 'media_object',
        fileNameProperty: 'filePath',
        size: 'size',
        mimeType: 'mimeType',
        originalName: 'originalFilename',
        dimensions: 'dimensions',
    )]
    #[Groups([
        'media_object:create',
    ])]
    #[Assert\NotBlank(groups: ['validation:media_object:create'])]
    private ?File $file = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[Groups([
        'media_object:acl:read',
    ])]
    private ?User $uploadedBy = null;

    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
    ])]
    private ?string $contentUrl = null;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'media_object:create',
    ])]
    private string $filePath;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
        'media_object:create',
    ])]
    private string $originalFilename;

    #[ORM\Column(type: 'string', length: 64, unique: true, options: ['fixed' => true])]
    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
        'media_object:create',
    ])]
    private string $sha256;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
        'media_object:create',
    ])]
    private string $mimeType;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
        'media_object:create',
    ])]
    private int $size;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
    ])]
    private ?int $width;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
    ])]
    private ?int $height;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
    ])]
    private \DateTimeImmutable $uploadDate;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'media_object:acl:read',
        'media_object:create',
        'media_object:update',
    ])]
    private string $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function getContentUrl(): ?string
    {
        return $this->contentUrl;
    }

    public function setContentUrl(?string $contentUrl): MediaObject
    {
        $this->contentUrl = $contentUrl;

        return $this;
    }

    #[Groups([
        'media_object:acl:read',
        'media_object_join:read',
    ])]
    public function getContentThumbnailUrl(): ?string
    {
        return in_array($this->getMimeType(), MediaObjectThumbnailer::SUPPORTED_FORMATS)
            ? preg_replace('/(?<filename>.+)(?<extension>\.\w+)?$/U', '$1.thumb.jpeg', $this->contentUrl)
            : null;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): MediaObject
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): MediaObject
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getSha256(): string
    {
        return $this->sha256;
    }

    public function setSha256(string $sha256): MediaObject
    {
        $this->sha256 = $sha256;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): MediaObject
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): MediaObject
    {
        $this->size = $size;

        return $this;
    }

    public function setId(int $id): MediaObject
    {
        $this->id = $id;

        return $this;
    }

    public function getDimensions(): ?array
    {
        return $this->width ? [$this->width, $this->height] : null;
    }

    #[Groups([
        'media_object:acl:read',
    ])]
    public function setDimensions(?array $dimensions): MediaObject
    {
        $this->width = $dimensions[0];
        $this->height = $dimensions[1];

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): MediaObject
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): MediaObject
    {
        $this->height = $height;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): MediaObject
    {
        $this->file = $file;

        return $this;
    }

    public function getUploadDate(): \DateTimeImmutable
    {
        return $this->uploadDate;
    }

    public function setUploadDate(\DateTimeImmutable $uploadDate): MediaObject
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): MediaObject
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): MediaObject
    {
        $this->description = $description;

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): MediaObject
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }
}
