<?php

namespace App\Tests\Utils;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait VichUploaderTestTrait
{
    private Filesystem $filesystem;
    private Finder $finder;

    private string $uploadBasePath = '';
    private string $testDataSourcePath = '';

    protected function resetVichUploaderTestDataDirectory(string $subDir, array $files = ['fake.csv', 'test.pdf']): void
    {
        $this->clearSubDirectories();
        $this->copyFilesToSubDirectory($subDir, $files);
    }

    protected function createUploadDirectoryTree(): void
    {
        try {
            foreach (['import', 'media'] as $subDirectory) {
                $this->getFilesystem()->mkdir(static::getUploadPath($subDirectory));
            }
        } catch (IOExceptionInterface $exception) {
            echo "Failed to create temporary directory: {$exception->getMessage()}. Skip\n";
        }
    }

    protected function removeUploadDirectoryTree(): void
    {
        try {
            $this->getFilesystem()->remove($this->getUploadPath());
        } catch (IOExceptionInterface $exception) {
            echo "Failed to remove temporary directory: {$exception->getMessage()}. Skip\n";
        }
    }

    protected function vichUploaderFileExists(string|array $filename): bool
    {
        return file_exists($this->getUploadPath($filename));
    }

    /**
     * @param string|array<string> $subTree
     */
    private function getUploadPath(string|array $subTree = ''): string
    {
        if (!$this->uploadBasePath) {
            $this->uploadBasePath = getenv('VICH_UPLOADER_UPLOAD_BASE_PATH');
            $this->validateUploadBasePath();
        }

        if (is_array($subTree)) {
            $subTree = implode(DIRECTORY_SEPARATOR, $subTree);
        }

        return $this->uploadBasePath
            ? $this->uploadBasePath.DIRECTORY_SEPARATOR.$subTree
            : $this->uploadBasePath;
    }

    private function isPathInTmpDir(): bool
    {
        $tmpDir = sys_get_temp_dir();

        // Normalize paths
        $tmpDir = rtrim(str_replace('\\', '/', realpath($tmpDir)));
        $path = rtrim(str_replace('\\', '/', $this->getUploadPath()));

        // Check if the path starts with tmp_dir/ and is not exactly tmp_dir
        return str_starts_with($path, $tmpDir.'/') && $path !== $tmpDir;
    }

    private function validateUploadBasePath(): bool
    {
        if (!$this->getUploadPath()) {
            throw new \LogicException('Upload base path is not set');
        }

        if (!$this->isPathInTmpDir()) {
            throw new \InvalidArgumentException('Upload base path must be in the system temp dir');
        }

        return true;
    }

    private function getSourcePath(string $fileName = ''): string
    {
        if (!$this->testDataSourcePath) {
            $this->testDataSourcePath = realpath(getenv('VICH_UPLOADER_SOURCE_PATH'));

            if (!$this->testDataSourcePath) {
                throw new \InvalidArgumentException('Unable to determine Vich Uploader test data source path');
            }
        }

        return $fileName ? $this->testDataSourcePath.DIRECTORY_SEPARATOR.$fileName : $this->testDataSourcePath;
    }

    private function clearSubDirectories(): void
    {
        foreach (['import', 'media'] as $dir) {
            $targetDir = static::getUploadPath($dir);

            if ($this->getFilesystem()->exists($targetDir)) {
                $this->getFinder()->in($targetDir)->ignoreDotFiles(false); // Include dotfiles

                foreach ($this->getFinder() as $file) {
                    $this->getFilesystem()->remove($file->getRealPath());
                }
            } else {
                echo "Directory '$targetDir' does not exist.\n";
            }
        }
    }

    private function copyFilesToSubDirectory(string $subDirectory, array $files): void
    {
        foreach ($files as $filename) {
            $sourceFile = $this->getSourcePath($filename);
            $destFile = static::getUploadPath($subDirectory.DIRECTORY_SEPARATOR.$filename);

            try {
                $this->getFilesystem()->copy($sourceFile, $destFile, true); // Overwrite if exists
            } catch (IOException $e) {
                echo "An error occurred while copying '$sourceFile': ".$e->getMessage()."\n";
            }
        }
    }

    private function getFilesystem(): Filesystem
    {
        if (!isset($this->filesystem)) {
            $this->filesystem = new Filesystem();
        }

        return $this->filesystem;
    }

    private function getFinder(): Finder
    {
        if (!isset($this->finder)) {
            $this->finder = new Finder();
        }

        return $this->finder;
    }
}
