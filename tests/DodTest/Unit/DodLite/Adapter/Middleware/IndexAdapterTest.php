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
