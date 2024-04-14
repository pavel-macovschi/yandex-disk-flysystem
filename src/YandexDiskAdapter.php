<?php

namespace ImpressiveWeb\Flysystem;

use ImpressiveWeb\YandexDisk\Client;
use ImpressiveWeb\YandexDisk\Exception\BadRequestException;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use League\Flysystem\WhitespacePathNormalizer;

class YandexDiskAdapter implements FilesystemAdapter
{
    protected $mimeTypeDetector;

    private WhitespacePathNormalizer $normalizer;

    public function __construct(
        private Client $client,
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
        $this->normalizer = new WhitespacePathNormalizer();
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function fileExists(string $path): bool
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $this->client->listContent($path, ['_embedded.items.path']);
            return true;
        } catch (BadRequestException | UnableToCheckFileExistence $e) {
            return false;
        }
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function directoryExists(string $path): bool
    {
        return $this->fileExists($path);
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $this->client->upload($path, $contents, true);
        } catch (BadRequestException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $this->client->upload($path, $contents, true);
        } catch (BadRequestException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function read(string $path): string
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $resource = $this->readStream($path);
            $contents = stream_get_contents($resource);
            fclose($resource);
            unset($resource);
        } catch (BadRequestException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }

        return $contents;
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function readStream(string $path)
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $location = $this->client->getDownloadUrl($path, ['href']);
            $stream = Utils::tryFopen($location['href'], 'r');
        } catch (BadRequestException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }

        return $stream;
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function delete(string $path): void
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $this->client->remove($path);
        } catch (BadRequestException $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function deleteDirectory(string $path, bool $destroy = false): void
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $this->client->remove($path);
        } catch (BadRequestException $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function createDirectory(string $path, Config $config): void
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $this->client->addDirectory($path);
        } catch (BadRequestException $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, sprintf('%s does not support visibility controls.', __CLASS__));
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function visibility(string $path): FileAttributes
    {
        $path = $this->normalizer->normalizePath($path);

        return new FileAttributes($path);
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function mimeType(string $path): FileAttributes
    {
        $path = $this->normalizer->normalizePath($path);

        return new FileAttributes(
            $path,
            null,
            null,
            null,
            $this->mimeTypeDetector->detectMimeTypeFromPath($path)
        );
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function lastModified(string $path): FileAttributes
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $data = $this->client->listContent($path, ['modified']);
        } catch (BadRequestException $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }

        $timestamp = (isset($data['modified'])) ? strtotime($data['modified']) : null;

        return new FileAttributes(
            $path,
            null,
            null,
            $timestamp
        );
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function fileSize(string $path): FileAttributes
    {
        $path = $this->normalizer->normalizePath($path);

        try {
            $data = $this->client->listContent($path, ['size']);
        } catch (BadRequestException $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }

        return new FileAttributes(
            $path,
            $data['size'] ?? null
        );
    }

    /**
     * @return iterable<StorageAttributes>
     *
     * @throws FilesystemException
     */
    public function listContents(string $path, bool $deep = false): iterable
    {
        $path = $this->normalizer->normalizePath($path);

        foreach ($this->iterateFolderContents($path, $deep) as $entry) {
            $attributes = $this->normalizeResponse($entry);

            // Avoid including the base directory itself.
            if ($attributes->isDir() && $attributes->path() === $path) {
                continue;
            }

            yield $attributes;
        }
    }

    /**
     * @throws UnableToMoveFile
     * @throws FilesystemException
     */
    public function move(string $source, string $destination, Config $config): void
    {
        $source = $this->normalizer->normalizePath($source);
        $destination = $this->normalizer->normalizePath($destination);

        try {
            $this->client->move($source, $destination);
        } catch (BadRequestException $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * @throws UnableToCopyFile
     * @throws FilesystemException
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $source = $this->normalizer->normalizePath($source);
        $destination = $this->normalizer->normalizePath($destination);

        try {
            $this->client->copy($source, $destination);
        } catch (BadRequestException $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    protected function iterateFolderContents(string $path, bool $deep): \Generator
    {
        $attributes = [
            '_embedded.items.path,
             _embedded.items.type',
        ];

        $data = $this->client->listContent(
            $path,
            $attributes,
            deep: $deep
        );

        yield from $data['_embedded']['items'];
    }

    protected function normalizeResponse(array $data): StorageAttributes
    {
        // Normalize path by removing an extra prefix.
        $path = str_replace($this->client->getPathPrefix(), '', $data['path']);
        $timestamp = (isset($data['modified'])) ? strtotime($data['modified']) : null;

        if ('dir' === $data['type']) {
            return new DirectoryAttributes(
                $path,
                null,
                $timestamp
            );
        }

        return new FileAttributes(
            $path,
            $data['size'] ?? null,
            null,
            $timestamp,
            $this->mimeTypeDetector->detectMimeTypeFromPath($path)
        );
    }
}
