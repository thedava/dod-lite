<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter\Middleware;

use DodLite\Adapter\MemoryAdapter;
use DodLite\Adapter\Middleware\IndexAdapter;

test('Write creates an index implicitly', function () {
    $memory = new MemoryAdapter();
    $indexAdapter = new IndexAdapter($memory, 'meta-test');

    $indexAdapter->write('test', 1, ['foo' => 'bar']);

    expect($memory->has('test', 1))->toBeTrue();
    expect($memory->has('meta-test', 'test.index'))->toBeTrue();
});

test('Creating index implicitly on existing data works', function () {
    $memory = new MemoryAdapter();
    $indexAdapter = new IndexAdapter($memory, 'meta-test');

    expect($indexAdapter->has('test', 1))->toBeFalse();
    $memory->write('test', 1, ['foo' => 'bar']);

    // Key has been added without using the IndexAdapter so the index should not have it
    expect($memory->has('test', 1))->toBeTrue();
    expect($indexAdapter->has('test', 1))->toBeFalse();

    // Delete the index meta file to force the IndexAdapter to reindex
    $indexAdapter->deleteIndex('test');
    expect($memory->has('test', 1))->toBeTrue();
    expect($indexAdapter->has('test', 1))->toBeTrue();
});

test('Creating index explicitly on existing data works', function () {
    $memory = new MemoryAdapter();
    $indexAdapter = new IndexAdapter($memory, 'meta-test');

    $memory->write('test', 1, ['foo' => 'bar']);
    $indexAdapter->recreateIndex('test');
    expect($indexAdapter->has('test', 1))->toBeTrue();
});
