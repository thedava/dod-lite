<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter\Middleware;

use DodLite\Adapter\MemoryAdapter;
use DodLite\Adapter\Middleware\ReplicateAdapter;

test('Write replication works', function () {
    $main = new MemoryAdapter();
    $replica = new MemoryAdapter();
    $replicateAdapter = new ReplicateAdapter($main, $replica);

    // Write via replicate adapter
    $replicateAdapter->write('test', 1, ['foo' => 'bar']);

    // Main and replica should have data now
    expect($main->has('test', 1))->toBeTrue();
    expect($replica->has('test', 1))->toBeTrue();
});

test('Delete replication works', function () {
    $main = new MemoryAdapter();
    $replica = new MemoryAdapter();
    $replicateAdapter = new ReplicateAdapter($main, $replica);

    // Write via replicate adapter
    $replicateAdapter->write('test', 1, ['foo' => 'bar']);

    // Main and replica should have data now
    expect($main->has('test', 1))->toBeTrue();
    expect($replica->has('test', 1))->toBeTrue();

    // Delete via replicate adapter
    $replicateAdapter->delete('test', 1);

    // Main and replica should have no data now
    expect($main->has('test', 1))->toBeFalse();
    expect($replica->has('test', 1))->toBeFalse();
});
