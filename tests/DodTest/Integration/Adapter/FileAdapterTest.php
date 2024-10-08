<?php
declare(strict_types=1);

namespace DodTest\Integration\Adapter;

use DodLite\Adapter\AdapterInterface;
use DodLite\Adapter\FileAdapter;
use DodLite\Exceptions\NotFoundException;
use DodLite\Filter\TrueFilter;

function createFileAdapter(string $test, bool $useGlob = false): AdapterInterface
{
    return new FileAdapter(
        createDodTempDir('fileAdapterTest-' . $test),
        useGlob: $useGlob,
    );
}

afterAll(function (): void {
    clearDodTempDir('fileAdapterTest-');
});

test('Reading non-existing data throws exception', function (): void {
    $fileAdapter = createFileAdapter('read-non-existing');

    $fileAdapter->read('collection', 'key');
})->throws(NotFoundException::class);

test('Writing and Reading data works', function (): void {
    $fileAdapter = createFileAdapter('write');

    $fileAdapter->write('collection', 'key', ['data' => 'value']);
    $data = $fileAdapter->read('collection', 'key');

    expect($data)->toBe(['data' => 'value']);
});

test('Deleting data works', function (): void {
    $fileAdapter = createFileAdapter('delete');

    $fileAdapter->write('collection', 'key', ['data' => 'value']);
    expect($fileAdapter->has('collection', 'key'))->toBeTrue();
    expect($fileAdapter->read('collection', 'key'))->toBe(['data' => 'value']);

    $fileAdapter->delete('collection', 'key');
    expect($fileAdapter->has('collection', 'key'))->toBeFalse();
});

test('readAll works', function (bool $useGlob): void {

    $fileAdapter = createFileAdapter('readAll', $useGlob);

    $fileAdapter->write('collection', 'key', ['data' => 'value']);
    $fileAdapter->write('collection', 'key2', ['data' => 'value2']);

    $documents = iterator_to_array($fileAdapter->readAll('collection', new TrueFilter()));
    expect($documents)
        ->toHaveKey('key')
        ->toHaveKey('key2');
})->with([
    false,
    true,
]);

test('readAll without data works', function (bool $useGlob): void {
    $fileAdapter = createFileAdapter('readAll-empty', $useGlob);

    $documents = iterator_to_array($fileAdapter->readAll('collection', new TrueFilter()));
    expect($documents)->toBe([]);
})->with([
    false,
    true,
]);

test('getAllCollectionNames works', function (bool $useGlob): void {
    $fileAdapter = createFileAdapter('getAllCollectionNames', $useGlob);

    $fileAdapter->write('collection', 'key', ['data' => 'value']);
    $fileAdapter->write('collection2', 'key2', ['data' => 'value2']);

    $collectionNames = iterator_to_array($fileAdapter->getAllCollectionNames());
    expect($collectionNames)->toContain('collection', 'collection2');
})->with([
    false,
    true,
]);
