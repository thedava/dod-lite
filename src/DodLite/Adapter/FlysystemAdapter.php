<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DodLite\Exceptions\DeleteFailedException;
use DodLite\Exceptions\NotFoundException;
use DodLite\Exceptions\WriteFailedException;
use DodLite\Normalizer\FileNameNormalizer;
use DodLite\Normalizer\JsonDecodeNormalizer;
use DodLite\Normalizer\JsonEncodeNormalizer;
use DodLite\Normalizer\NormalizerInterface;
use Generator;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use Throwable;

class FlysystemAdapter implements AdapterInterface
{
    private const FILE_EXTENSION = 'db.json';

    private readonly NormalizerInterface $idNormalizer;
    private readonly NormalizerInterface $collectionNormalizer;

    private readonly NormalizerInterface $dataEncoder;
    private readonly NormalizerInterface $dataDecoder;

    public function __construct(
        private readonly Filesystem $filesystem,
        ?NormalizerInterface $idNormalizer = null,
        ?NormalizerInterface $collectionNormalizer = null,
        ?NormalizerInterface $dataEncoder = null,
        ?NormalizerInterface $dataDecoder = null,
    )
    {
        $this->idNormalizer = $idNormalizer ?? new FileNameNormalizer();
        $this->collectionNormalizer = $collectionNormalizer ?? new FileNameNormalizer();
        $this->dataEncoder = $dataEncoder ?? new JsonEncodeNormalizer();
        $this->dataDecoder = $dataDecoder ?? new JsonDecodeNormalizer();
    }

    private function getPath(string $collection, string|int $id): string
    {
        return sprintf(
            '%s/%s.%s',
            $this->collectionNormalizer->normalize($collection),
            $this->idNormalizer->normalize((string)$id),
            self::FILE_EXTENSION,
        );
    }

    public function has(string $collection, string|int $id): bool
    {
        try {
            $this->read($collection, $id);

            return true;
        } catch (NotFoundException) {
            return false;
        }
    }

    public function write(string $collection, string|int $id, array $data): void
    {
        try {
            $this->filesystem->write(
                $this->getPath($collection, $id),
                (string)$this->dataEncoder->normalize($data),
            );
        } catch (Throwable $e) {
            throw new WriteFailedException($collection, $id, $e);
        }
    }

    private function readPath(string $collection, string|int $id, string $path): array
    {
        try {
            $data = $this->filesystem->read($path);
            assert(is_string($data));

            return $this->dataDecoder->normalize($data);
        } catch (UnableToReadFile $e) {
            throw new NotFoundException($collection, $id, $e);
        }
    }

    public function read(string $collection, string|int $id): array
    {
        return $this->readPath($collection, $id, $this->getPath($collection, $id));
    }

    public function delete(string $collection, string|int $id): void
    {
        try {
            $this->filesystem->delete($this->getPath($collection, $id));
        } catch (Throwable $e) {
            throw new DeleteFailedException($collection, $id, $e);
        }
    }

    public function readAll(string $collection): Generator
    {
        try {
            $contents = $this->filesystem->listContents($this->collectionNormalizer->normalize($collection));
            foreach ($contents->getIterator() as $item) {
                if ($item instanceof FileAttributes) {
                    $key = basename($item->path(), '.' . self::FILE_EXTENSION);

                    yield $key => $this->readPath($collection, $key, $item->path());
                }
            }
        } catch (FilesystemException $e) {
            throw new NotFoundException($collection, id: null, previous: $e);
        }
    }
}
