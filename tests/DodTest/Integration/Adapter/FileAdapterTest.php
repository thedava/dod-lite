<?php
declare(strict_types=1);

namespace DodTest\Integration\Adapter;

use DodLite\Adapter\FileAdapter;
use DodLite\Exceptions\NotFoundException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

function getTempDir(bool $create): string
{
    $tempDir = __DIR__ . DIRECTORY_SEPARATOR . 'dod-lite' . DIRECTORY_SEPARATOR . 'tmp-' . uniqid('', true);

    if ($create) {
        mkdir($tempDir, 0777, true);
    }

    return $tempDir;
}

function createFileAdapter(): FileAdapter
{
    return new FileAdapter(
        getTempDir(true),
    );
}

afterAll(function (): void {
    if (is_dir($dir = dirname(getTempDir(false)))) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileInfo) {
            $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileInfo->getRealPath());
        }

        rmdir($dir);
    }
});

test('Reading non-existing data throws exception', function (): void {
    $fileAdapter = createFileAdapter();

    $fileAdapter->read('collection', 'key');
})->throws(NotFoundException::class);

test('Writing and Reading data works', function (): void {
    $fileAdapter = createFileAdapter();

    $fileAdapter->write('collection', 'key', ['data' => 'value']);
    $data = $fileAdapter->read('collection', 'key');

    expect($data)->toBe(['data' => 'value']);
});

test('Deleting data works', function (): void {
    $fileAdapter = createFileAdapter();

    $fileAdapter->write('collection', 'key', ['data' => 'value']);
    expect($fileAdapter->has('collection', 'key'))->toBeTrue();
    expect($fileAdapter->read('collection', 'key'))->toBe(['data' => 'value']);

    $fileAdapter->delete('collection', 'key');
    expect($fileAdapter->has('collection', 'key'))->toBeFalse();
});

test('readAll works', function (): void {
    $fileAdapter = createFileAdapter();

    $fileAdapter->write('collection', 'key', ['data' => 'value']);
    $fileAdapter->write('collection', 'key2', ['data' => 'value2']);

    $documents = iterator_to_array($fileAdapter->readAll('collection'));
    expect($documents)
        ->toHaveKey('key')
        ->toHaveKey('key2');
});


test('readAll without data works', function (): void {
    $fileAdapter = createFileAdapter();

    $documents = iterator_to_array($fileAdapter->readAll('collection'));
    expect($documents)->toBe([]);
});
