<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter\Middleware;

use DodLite\Adapter\MemoryAdapter;
use DodLite\Adapter\Middleware\ReadOnlyAdapter;
use DodLite\Exceptions\ReadOnlyException;
use DodLite\Filter\TrueFilter;

test('Reading data works', function () {
    $memory = new MemoryAdapter();
    $readOnly = new ReadOnlyAdapter($memory, false);

    // Write via memory, read via readOnly
    $memory->write('test', 1, ['foo' => 'bar']);
    $data = $readOnly->read('test', 1);
    expect($data)->toBe(['foo' => 'bar']);
});

test('Reading all data works', function () {
    $memory = new MemoryAdapter();
    $readOnly = new ReadOnlyAdapter($memory, false);

    // Write via memory, read via readOnly
    $memory->write('test', 1, ['foo' => 'bar']);
    $data = iterator_to_array($readOnly->readAll('test', new TrueFilter()));
    expect($data)->toHaveKey(1);
});

test('Writing data fails silently', function () {
    $readOnly = new ReadOnlyAdapter(new MemoryAdapter(), false);

    $readOnly->write('test', 1, ['foo' => 'bar']);
    expect($readOnly->has('test', 1))->toBeFalse();
});

test('Writing data fails with exception', function () {
    $readOnly = new ReadOnlyAdapter(new MemoryAdapter(), true);

    $readOnly->write('test', 1, ['foo' => 'bar']);
})->throws(ReadOnlyException::class);

test('Deleting data fails silently', function () {
    $memory = new MemoryAdapter();
    $readOnly = new ReadOnlyAdapter($memory, false);

    // Write via memory, delete via readOnly
    $memory->write('test', 1, ['foo' => 'bar']);
    $readOnly->delete('test', 1);
    expect($readOnly->has('test', 1))->toBeTrue();
});

test('Deleting data fails with exception', function () {
    $memory = new MemoryAdapter();
    $readOnly = new ReadOnlyAdapter($memory, true);

    // Write via memory, delete via readOnly
    $memory->write('test', 1, ['foo' => 'bar']);
    $readOnly->delete('test', 1);
})->throws(ReadOnlyException::class);
