<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter;

use DodLite\Adapter\MemoryAdapter;
use DodLite\Exceptions\NotFoundException;


test('Reading non-existing data throws exception', function (): void {
    $flysystemAdapter = new \DodLite\Adapter\MemoryAdapter();

    $flysystemAdapter->read('collection', 'key');
})->throws(NotFoundException::class);

test('Writing and Reading data works', function (): void {
    $flysystemAdapter = new \DodLite\Adapter\MemoryAdapter();

    $flysystemAdapter->write('collection', 'key', ['data' => 'value']);
    $data = $flysystemAdapter->read('collection', 'key');

    expect($data)->toBe(['data' => 'value']);
});

test('Deleting data works', function (): void {
    $flysystemAdapter = new \DodLite\Adapter\MemoryAdapter();

    $flysystemAdapter->write('collection', 'key', ['data' => 'value']);
    expect($flysystemAdapter->has('collection', 'key'))->toBeTrue();
    expect($flysystemAdapter->read('collection', 'key'))->toBe(['data' => 'value']);

    $flysystemAdapter->delete('collection', 'key');
    expect($flysystemAdapter->has('collection', 'key'))->toBeFalse();
});

test('readAll works', function (): void {
    $flysystemAdapter = new MemoryAdapter();

    $flysystemAdapter->write('collection', 'key', ['data' => 'value']);
    $flysystemAdapter->write('collection', 'key2', ['data' => 'value2']);

    $documents = iterator_to_array($flysystemAdapter->readAll('collection'));
    expect($documents)
        ->toHaveKey('key')
        ->toHaveKey('key2');
});


test('readAll without data works', function (): void {
    $flysystemAdapter = new \DodLite\Adapter\MemoryAdapter();

    $documents = iterator_to_array($flysystemAdapter->readAll('collection'));
    expect($documents)->toBe([]);
});
