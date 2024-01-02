<?php
declare(strict_types=1);

namespace DodTest\Integration\Adapter;

use DodLite\Adapter\AdapterInterface;
use DodLite\Adapter\FileAdapter;
use DodLite\Adapter\Middleware\IndexAdapter;
use DodLite\Exceptions\NotFoundException;

function createFileAdapter(string $test): AdapterInterface
{
    return new IndexAdapter(
        new FileAdapter(
            createDodTempDir('fileAdapterTest-' . $test),
        )
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

test('readAll works', function (): void {

    $fileAdapter = createFileAdapter('readAll');

    $fileAdapter->write('collection', 'key', ['data' => 'value']);
    $fileAdapter->write('collection', 'key2', ['data' => 'value2']);

    $documents = iterator_to_array($fileAdapter->readAll('collection'));
    expect($documents)
        ->toHaveKey('key')
        ->toHaveKey('key2');
})->skip(DOD_TEST_ENV === 'github', 'Skipped due to unknown problem with github and this test');


test('readAll without data works', function (): void {
    $fileAdapter = createFileAdapter('readAll-empty');

    $documents = iterator_to_array($fileAdapter->readAll('collection'));
    expect($documents)->toBe([]);
})->skip(DOD_TEST_ENV === 'github', 'Skipped due to unknown problem with github and this test');
