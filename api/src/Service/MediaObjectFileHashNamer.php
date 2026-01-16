<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Data\MediaObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

readonly class MediaObjectFileHashNamer implements NamerInterface
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function name($object, PropertyMapping $mapping): string
    {
        if (!$object instanceof MediaObject) {
            throw new \InvalidArgumentException('object to rename must be instance of '.MediaObject::class);
        }
        /* @var $file UploadedFile */
        $file = $mapping->getFile($object);
        $name = $file->getClientOriginalName();

        return sprintf('%s_%s', substr($object->getSha256(), 0, 16), $this->transliterate($name));
    }

    public function transliterate(string $string): string
    {
        $path_parts = pathinfo($string);

        $filename = [
            $this->slugger->slug($path_parts['filename']),
            array_key_exists('extension', $path_parts) ? $path_parts['extension'] : null,
        ];
        $transliterated = implode('.', array_filter($filename));

        return strtolower($transliterated);
    }
}
