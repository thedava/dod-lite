<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter;

use DodLite\Adapter\MemoryAdapter;
use DodLite\Exceptions\NotFoundException;
use DodLite\Filter\TrueFilter;


test('Reading non-existing data throws exception', function (): void {
    $memoryAdapter = new MemoryAdapter();

    $memoryAdapter->read('collection', 'key');
})->throws(NotFoundException::class);

test('Writing and Reading data works', function (): void {
    $memoryAdapter = new MemoryAdapter();

    $memoryAdapter->write('collection', 'key', ['data' => 'value']);
    $data = $memoryAdapter->read('collection', 'key');

    expect($data)->toBe(['data' => 'value']);
});

test('Deleting data works', function (): void {
    $memoryAdapter = new MemoryAdapter();

    $memoryAdapter->write('collection', 'key', ['data' => 'value']);
    expect($memoryAdapter->has('collection', 'key'))->toBeTrue();
    expect($memoryAdapter->read('collection', 'key'))->toBe(['data' => 'value']);

    $memoryAdapter->delete('collection', 'key');
    expect($memoryAdapter->has('collection', 'key'))->toBeFalse();
});

test('readAll works', function (): void {
    $memoryAdapter = new MemoryAdapter();

    $memoryAdapter->write('collection', 'key', ['data' => 'value']);
    $memoryAdapter->write('collection', 'key2', ['data' => 'value2']);

    $documents = iterator_to_array($memoryAdapter->readAll('collection', new TrueFilter()));
    expect($documents)
        ->toHaveKey('key')
        ->toHaveKey('key2');
});

test('readAll without data works', function (): void {
    $memoryAdapter = new MemoryAdapter();

    $documents = iterator_to_array($memoryAdapter->readAll('collection', new TrueFilter()));
    expect($documents)->toBe([]);
});

test('getAllCollectionNames works', function (): void {
    $memoryAdapter = new MemoryAdapter();

    $memoryAdapter->write('collection', 'key', ['data' => 'value']);
    $memoryAdapter->write('collection2', 'key2', ['data' => 'value2']);

    $collectionNames = $memoryAdapter->getAllCollectionNames();
    expect($collectionNames)->toContain('collection', 'collection2');
});

test('Disposing works', function (): void {
    $memoryAdapter = new MemoryAdapter();

    $memoryAdapter->write('collection', 'key', ['data' => 'value']);
    $memoryAdapter->write('collection-two', 'key', ['data' => 'value']);
    expect($memoryAdapter->has('collection', 'key'))->toBeTrue();
    expect($memoryAdapter->has('collection-two', 'key'))->toBeTrue();

    $memoryAdapter->dispose();

    expect($memoryAdapter->has('collection', 'key'))->toBeFalse();
    expect($memoryAdapter->has('collection-two', 'key'))->toBeFalse();
});
