<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DirectoryIterator;
use DodLite\Exceptions\Adapter\AdapterInitializationFailedException;
use DodLite\Exceptions\Adapter\FileAdapterFunctionFailedException;
use DodLite\Exceptions\DeleteFailedException;
use DodLite\Exceptions\NotFoundException;
use DodLite\Exceptions\WriteFailedException;
use DodLite\Filter\FilterInterface;
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
        string                 $rootPath,
        protected readonly int $filePermissions = 0777,
        private readonly int   $directoryPermissions = 0777,
        private readonly bool  $useGlob = false,
    )
    {
        $realRootPath = @realpath($rootPath);
        if (empty($realRootPath)) {
            throw new AdapterInitializationFailedException(
                sprintf('Given rootPath "%s" not found!', $rootPath),
                previous: $this->functionFailed('realpath', false, $rootPath),
            );
        }
        $this->rootPath = $realRootPath;

        $this->idNormalizer = new FileNameNormalizer();
        $this->collectionNormalizer = new FileNameNormalizer();
        $this->dataEncoder = new JsonEncodeNormalizer();
        $this->dataDecoder = new JsonDecodeNormalizer();
    }

    protected function functionFailed(string $function, mixed $result, string $path): FileAdapterFunctionFailedException
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

    /**
     * Compute the absolute file path for a given collection/id.
     * Made protected so subclasses (e.g. AtomicFileAdapter) can reuse it.
     */
    protected function getPath(string $collection, string|int|null $id): string
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

            // 1) Ensure collection directory exists
            $this->ensureCollectionDir(dirname($path));

            // 2) Ensure file exists and has correct permissions
            $this->ensureFileExistsWithMode($path);

            // 3) Perform the actual write operation
            $payload = (string)$this->dataEncoder->normalize($data);
            $this->writeString($path, $payload);
        } catch (Throwable $e) {
            throw new WriteFailedException($collection, $id, $e);
        }
    }

    /**
     * Hook: ensure the directory for a collection exists.
     */
    protected function ensureCollectionDir(string $dir): void
    {
        if (!is_dir($dir)) {
            $result = @mkdir($dir, permissions: $this->directoryPermissions, recursive: true);
            if ($result !== true) {
                throw $this->functionFailed('mkdir', $result, $dir);
            }
        }
    }

    /**
     * Hook: ensure the file exists and set its mode.
     * In AtomicFileAdapter this becomes a no-op.
     */
    protected function ensureFileExistsWithMode(string $path): void
    {
        if (!file_exists($path)) {
            $result = @touch($path);
            if ($result !== true) {
                throw $this->functionFailed('touch', $result, $path);
            }

            $result = @chmod($path, $this->filePermissions);
            if ($result !== true) {
                throw $this->functionFailed('chmod', $result, $path);
            }
        }
    }

    /**
     * Hook: perform the actual write.
     * Default: direct file_put_contents.
     */
    protected function writeString(string $path, string $payload): void
    {
        $result = @file_put_contents($path, $payload);
        if ($result === false || $result === 0) {
            throw $this->functionFailed('file_put_contents', $result, $path);
        }
    }

    /**
     * Made protected so subclasses can reuse it.
     */
    protected function readPath(string $collection, string|int $id, string $path): array
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
            $this->unlinkPath($path);
        } catch (Throwable $e) {
            throw new DeleteFailedException($collection, $id, $e);
        }
    }

    /**
     * Hook: remove a file. AtomicFileAdapter overrides this.
     */
    protected function unlinkPath(string $path): void
    {
        if (!@unlink($path)) {
            throw $this->functionFailed('unlink', false, $path);
        }
    }

    public function readAll(string $collection, FilterInterface $filter): Generator
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

    public function getAllCollectionNames(): Generator
    {
        if ($this->useGlob) {
            foreach (glob($this->rootPath . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR, GLOB_ONLYDIR) as $dir) {
                yield basename($dir);
            }

            return;
        }

        $iterator = new DirectoryIterator($this->rootPath);
        while ($iterator->valid()) {
            if (!$iterator->current()->isDot() && $iterator->current()->isDir()) {
                yield $iterator->getBasename();
            }
            $iterator->next();
        }
    }
}
