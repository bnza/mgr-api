<?php

namespace App\Tests\Functional;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait ApiTestFileUploadTrait
{
    private FileLocatorInterface $locator;
    private string $projectDir;

    protected function setUpTestFileUpload(Container $container): void
    {
        $this->locator = $container->get('file_locator');
        $this->projectDir = $container->getParameter('kernel.project_dir');
    }

    protected function getProjectDir(): string
    {
        return $this->projectDir;
    }

    protected function getFixturesFilePath(string $fileName, ?string $path = ''): string
    {
        $filePath = $this->getProjectDir().DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'input';
        if ($path) {
            if ('/' === $path[0]) {
                $filePath = $path;
            } else {
                $filePath .= DIRECTORY_SEPARATOR.$path;
            }
        }

        return $filePath.DIRECTORY_SEPARATOR.$fileName;
    }

    protected function getTestUploadFile(string $fileName, ?string $path = ''): UploadedFile
    {
        $tempFileName = tempnam(sys_get_temp_dir(), 'api_test_');
        if (false === $tempFileName) {
            throw new \RuntimeException('Could not create temporary file.');
        }

        if (!copy($this->getFixturesFilePath($fileName, $path), $tempFileName)) {
            unlink($tempFileName); // Cleanup if copy fails
            throw new \RuntimeException('Could not copy file.');
        }

        return new UploadedFile(
            $tempFileName,
            $fileName,
            null,
            null,
            true
        );
    }
}
