<?php
declare(strict_types=1);

namespace DodTest\Integration\Adapter;

use DodLite\Adapter\FlysystemAdapter;
use DodLite\Data\Document;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

function createFlysystemAdapter(): FlysystemAdapter
{
    return new FlysystemAdapter(
        new Filesystem(
            new InMemoryFilesystemAdapter()
        )
    );
}

test('Writing and Reading data works', function (): void {
    $flysystemAdapter = createFlysystemAdapter();

    $flysystemAdapter->write('collection', new Document('key', ['data' => 'value']));
    $key = $flysystemAdapter->read('collection', 'key');

    expect($key->getContent())->toBe(['data' => 'value']);
});

test('Deleting data works', function () {
    $flysystemAdapter = createFlysystemAdapter();

    $flysystemAdapter->write('collection', new Document('key', ['data' => 'value']));
    expect($flysystemAdapter->has('collection', 'key'))->toBeTrue();
    expect($flysystemAdapter->read('collection', 'key')->getContent())->toBe(['data' => 'value']);

    $flysystemAdapter->delete('collection', 'key');
    expect($flysystemAdapter->has('collection', 'key'))->toBeFalse();
});

test('readAll works', function (): void {
    $flysystemAdapter = createFlysystemAdapter();

    $flysystemAdapter->write('collection', new Document('key', ['data' => 'value']));
    $flysystemAdapter->write('collection', new Document('key2', ['data' => 'value2']));

    $documents = $flysystemAdapter->readAll('collection');
    expect($documents)
        ->toHaveKey('key')
        ->toHaveKey('key2');
});
