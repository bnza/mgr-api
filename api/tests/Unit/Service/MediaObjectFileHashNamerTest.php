<?php

namespace App\Tests\Unit\Service;

use App\Service\MediaObjectFileHashNamer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class MediaObjectFileHashNamerTest extends TestCase
{
    private SluggerInterface $slugger;
    private MediaObjectFileHashNamer $namer;

    protected function setUp(): void
    {
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->namer = new MediaObjectFileHashNamer($this->slugger);
    }

    public function testTransliterateWithExtension(): void
    {
        // Arrange
        $unicodeString = $this->createMock(UnicodeString::class);
        $unicodeString->method('__toString')->willReturn('hello-world');

        $this->slugger->method('slug')->with('hello world')->willReturn($unicodeString);

        // Act
        $result = $this->namer->transliterate('hello world.txt');

        // Assert
        $this->assertEquals('hello-world.txt', $result);
    }

    public function testTransliterateWithoutExtension(): void
    {
        // Arrange
        $unicodeString = $this->createMock(UnicodeString::class);
        $unicodeString->method('__toString')->willReturn('filename-only');

        $this->slugger->method('slug')->with('filename only')->willReturn($unicodeString);

        // Act
        $result = $this->namer->transliterate('filename only');

        // Assert
        $this->assertEquals('filename-only', $result);
    }
}
