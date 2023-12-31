<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter\Middleware;

use DodLite\Adapter\MemoryAdapter;
use DodLite\Adapter\Middleware\FallbackAdapter;

test('Reading data from secondary works', function () {
    $primary = new MemoryAdapter();
    $secondary = new MemoryAdapter();
    $fallbackAdapter = new FallbackAdapter($primary, $secondary, updatePrimaryOnFailedRead: false);

    // Store different data in primary and secondary
    $primary->write('test', 1, ['foo' => 'bar']);
    $secondary->write('test', 1, ['foo' => 'baz']);

    // Fallback should return data from primary
    $data = $fallbackAdapter->read('test', 1);
    expect($data)->toBe(['foo' => 'bar']);
});

test('Primary has no data and secondary is used instead', function () {
    $primary = new MemoryAdapter();
    $secondary = new MemoryAdapter();
    $fallbackAdapter = new FallbackAdapter($primary, $secondary, updatePrimaryOnFailedRead: false);

    // Store data in secondary
    $secondary->write('test', 1, ['foo' => 'bar']);

    // Fallback should return data from secondary
    $data = $fallbackAdapter->read('test', 1);
    expect($data)->toBe(['foo' => 'bar']);

    // Primary should still have no data
    expect($primary->has('test', 1))->toBeFalse();
});

test('Primary has no data and secondary is used instead (but primary will be updated)', function () {
    $primary = new MemoryAdapter();
    $secondary = new MemoryAdapter();
    $fallbackAdapter = new FallbackAdapter($primary, $secondary, updatePrimaryOnFailedRead: true);

    // Store data in secondary
    $secondary->write('test', 1, ['foo' => 'bar']);

    // Fallback should return data from secondary
    $data = $fallbackAdapter->read('test', 1);
    expect($data)->toBe(['foo' => 'bar']);

    // Primary should have data now
    expect($primary->has('test', 1))->toBeTrue();
});
