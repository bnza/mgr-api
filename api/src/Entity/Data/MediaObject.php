<?php

namespace App\Entity\Data;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\Service\MediaObjectThumbnailer;
use App\State\MediaObjectPostProcessor;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Entity]
#[Table(name: 'media_objects')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                            ],
                        ],
                    ])
                )
            ),
            denormalizationContext: ['groups' => ['media:object:create']],
            validationContext: ['groups' => ['validation:media:object:create']],
            processor: MediaObjectPostProcessor::class,
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['media:object:acl:read']]
)]
#[Vich\Uploadable]
class MediaObject
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'media:object:acl:read',
    ])]
    private int $id;

    #[Vich\UploadableField(
        mapping: 'media_object',
        fileNameProperty: 'filePath',
        size: 'size',
        mimeType: 'mimeType',
        originalName: 'originalFilename',
        dimensions: 'dimensions',
    )]
    #[Groups([
        'media:object:create',
    ])]
    private ?File $file = null;

    #[Groups([
        'media:object:acl:read',
    ])]
    private ?string $contentUrl = null;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'media:object:create',
    ])]
    private string $filePath;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'media:object:acl:read',
        'media:object:create',
    ])]
    private string $originalFilename;

    #[ORM\Column(type: 'string', length: 64, unique: true, options: ['fixed' => true])]
    #[Groups([
        'media:object:acl:read',
        'media:object:create',
    ])]
    private string $sha256;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'media:object:acl:read',
        'media:object:create',
    ])]
    private string $mimeType;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'media:object:acl:read',
        'media:object:create',
    ])]
    private int $size;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'media:object:acl:read',
    ])]
    private ?int $width;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'media:object:acl:read',
    ])]
    private ?int $height;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups([
        'media:object:acl:read',
    ])]
    private \DateTimeImmutable $uploadDate;

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
        'media:object:acl:read',
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
        'media:object:acl:read',
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
}
