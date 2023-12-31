<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter\Middleware;

use DodLite\Adapter\MemoryAdapter;
use DodLite\Adapter\Middleware\LockAdapter;

test('Write works', function () {
    $memory = new MemoryAdapter();
    $lockAdapter = new LockAdapter($memory);

    expect($lockAdapter->has('foo', 'bar'))->toBeFalse();
    $lockAdapter->write('foo', 'bar', ['baz' => 'qux']);
    expect($lockAdapter->has('foo', 'bar'))->toBeTrue();
});

test('Second write works (lock is properly released)', function () {
    $memory = new MemoryAdapter();
    $lockAdapter = new LockAdapter($memory);

    expect($lockAdapter->has('foo', 'bar'))->toBeFalse();
    $lockAdapter->write('foo', 'bar', ['baz' => 'qux']);
    expect($lockAdapter->has('foo', 'bar'))->toBeTrue();
    $lockAdapter->write('foo', 'bar', ['foo' => 'bar']);
    expect($lockAdapter->read('foo', 'bar'))->toBe(['foo' => 'bar']);
});

test('Delete works', function () {
    $memory = new MemoryAdapter();
    $lockAdapter = new LockAdapter($memory);

    $lockAdapter->write('foo', 'bar', ['baz' => 'qux']);
    expect($lockAdapter->has('foo', 'bar'))->toBeTrue();
    $lockAdapter->delete('foo', 'bar');
    expect($lockAdapter->has('foo', 'bar'))->toBeFalse();
});
