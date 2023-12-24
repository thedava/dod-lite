<?php
declare(strict_types=1);

namespace DodLite\Adapter;

use DodLite\Data\Document;
use DodLite\KeyNotFoundException;
use DodLite\Normalizer\DataToFileNormalizer;
use DodLite\Normalizer\FileToDataNormalizer;
use DodLite\Normalizer\KeyNormalizer;
use DodLite\Normalizer\NormalizerInterface;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToReadFile;

class FlysystemAdapter extends AbstractAdapter implements AdapterInterface
{
    private const FILE_EXTENSION = 'db.json';

    private readonly NormalizerInterface $keyNormalizer;
    private readonly NormalizerInterface $collectionNormalizer;

    private readonly NormalizerInterface $dataSerializer;
    private readonly NormalizerInterface $dataDeserializer;

    public function __construct(
        private readonly Filesystem $filesystem,
    )
    {
        $this->keyNormalizer = new KeyNormalizer();
        $this->collectionNormalizer = new KeyNormalizer();

        $this->dataSerializer = new DataToFileNormalizer();
        $this->dataDeserializer = new FileToDataNormalizer();
    }

    private function getPath(string $collection, string|Document $key): string
    {
        return sprintf(
            '%s/%s.%s',
            $this->keyNormalizer->normalize($collection),
            $this->keyNormalizer->normalize((string)$key),
            self::FILE_EXTENSION,
        );
    }

    public function write(string $collection, Document $data): void
    {
        $this->filesystem->write(
            $this->getPath($collection, $data),
            (string)$this->dataSerializer->normalize($data->getContent()),
        );
    }

    private function readPath(string $collection, string $key, string $path): Document
    {
        try {
            $data = $this->filesystem->read($path);
            assert(is_string($data));

            return new Document(
                $key,
                $this->dataDeserializer->normalize($data),
            );
        } catch (UnableToReadFile $e) {
            throw new KeyNotFoundException(sprintf('Key "%s" not found in collection "%s"', $key, $collection), previous: $e);
        }
    }

    public function read(string $collection, string $key): Document
    {
        return $this->readPath($collection, $key, $this->getPath($collection, $key));
    }

    public function delete(string $collection, string $key): void
    {
        $this->filesystem->delete($this->getPath($collection, $key));
    }

    public function readAll(string $collection): array
    {
        $documents = [];
        $contents = $this->filesystem->listContents($this->collectionNormalizer->normalize($collection));
        foreach ($contents->getIterator() as $item) {
            if ($item instanceof FileAttributes) {
                $key = basename($item->path(), '.' . self::FILE_EXTENSION);
                $documents[$key] = $this->readPath($collection, $key, $item->path());
            }
        }

        return $documents;
    }
}
