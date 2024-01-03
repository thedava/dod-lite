<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DirectoryIterator;
use DodLite\DodException;
use DodLite\Exceptions\Adapter\FileAdapterFunctionFailedException;
use DodLite\Exceptions\DeleteFailedException;
use DodLite\Exceptions\NotFoundException;
use DodLite\Exceptions\WriteFailedException;
use DodLite\Normalizer\FileNameNormalizer;
use DodLite\Normalizer\JsonDecodeNormalizer;
use DodLite\Normalizer\JsonEncodeNormalizer;
use DodLite\Normalizer\NormalizerInterface;
use Generator;
use Throwable;

class FileAdapter implements AdapterInterface
{
    private const FILE_EXTENSION = '.db.json';

    private readonly string $rootPath;

    private readonly NormalizerInterface $idNormalizer;
    private readonly NormalizerInterface $collectionNormalizer;

    private readonly NormalizerInterface $dataEncoder;
    private readonly NormalizerInterface $dataDecoder;

    public function __construct(
        string                $rootPath,
        private readonly int $filePermissions = 0777,
        private readonly int $directoryPermissions = 0777,
        private readonly bool $useGlob = false,
    )
    {
        $rootPath = realpath($rootPath);
        if (empty($rootPath)) {
            throw new DodException(
                sprintf('Given rootPath "%s" not found!', $rootPath),
                previous: $this->functionFailed('realpath', false, $rootPath),
            );
        }
        $this->rootPath = $rootPath;

        $this->idNormalizer = new FileNameNormalizer();
        $this->collectionNormalizer = new FileNameNormalizer();
        $this->dataEncoder = new JsonEncodeNormalizer();
        $this->dataDecoder = new JsonDecodeNormalizer();
    }

    private function functionFailed(string $function, mixed $result, string $path): FileAdapterFunctionFailedException
    {
        return new FileAdapterFunctionFailedException(
            $function,
            $path,
            $result,
            $this->rootPath,
            $this->filePermissions,
            $this->directoryPermissions,
            $this->useGlob,
        );
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    private function getPath(string $collection, string|int|null $id): string
    {
        $pathParts = [
            $this->rootPath,
            $this->collectionNormalizer->normalize($collection),
        ];
        if ($id !== null) {
            $pathParts[] = $this->idNormalizer->normalize((string)$id) . self::FILE_EXTENSION;
        }

        return implode(DIRECTORY_SEPARATOR, $pathParts);
    }

    public function has(string $collection, string|int $id): bool
    {
        return file_exists($this->getPath($collection, $id));
    }

    public function write(string $collection, string|int $id, array $data): void
    {
        try {
            $path = $this->getPath($collection, $id);

            // Create collection directory recursively
            $dir = dirname($path);
            if (!is_dir($dir)) {
                $result = mkdir($dir, permissions: $this->directoryPermissions, recursive: true);
                if ($result !== true) {
                    throw $this->functionFailed('mkdir', $result, $dir);
                }
            }

            // Create file and change chmod before writing
            if (!file_exists($path)) {
                $result = touch($path);
                if ($result !== true) {
                    throw $this->functionFailed('touch', $result, $path);
                }

                $result = chmod($path, $this->filePermissions);
                if ($result !== true) {
                    throw $this->functionFailed('chmod', $result, $path);
                }
            }

            // Write data to file
            $result = file_put_contents($path, (string)$this->dataEncoder->normalize($data));
            if ($result === false || $result === 0) {
                throw $this->functionFailed('file_put_contents', $result, $path);
            }
        } catch (Throwable $e) {
            throw new WriteFailedException($collection, $id, $e);
        }
    }

    private function readPath(string $collection, string|int $id, string $path): array
    {
        if (!file_exists($path)) {
            throw new NotFoundException(
                $collection,
                $id,
                $this->functionFailed('file_exists', false, $path)
            );
        }

        return $this->dataDecoder->normalize(
            file_get_contents($path)
        );
    }

    public function read(string $collection, string|int $id): array
    {
        return $this->readPath($collection, $id, $this->getPath($collection, $id));
    }

    public function delete(string $collection, string|int $id): void
    {
        try {
            $path = $this->getPath($collection, $id);

            if (!unlink($path)) {
                throw $this->functionFailed('unlink', false, $path);
            }
        } catch (Throwable $e) {
            throw new DeleteFailedException($collection, $id, $e);
        }
    }

    public function readAll(string $collection): Generator
    {
        $path = $this->getPath($collection, null);
        if (!is_dir($path)) {
            return;
        }

        if ($this->useGlob) {
            foreach (glob($path . DIRECTORY_SEPARATOR . '*' . self::FILE_EXTENSION) as $file) {
                yield basename($file, self::FILE_EXTENSION) => $this->readPath($collection, basename($file, self::FILE_EXTENSION), $file);
            }

            return;
        }

        $iterator = new DirectoryIterator($path);
        while ($iterator->valid()) {
            if (!$iterator->current()->isDot()) {
                yield $iterator->getBasename(self::FILE_EXTENSION) => $this->readPath($collection, $iterator->getBasename(self::FILE_EXTENSION), $iterator->getPathname());
            }
            $iterator->next();
        }
    }
}
