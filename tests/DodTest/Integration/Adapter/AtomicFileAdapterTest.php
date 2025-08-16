<?php
declare(strict_types=1);

namespace DodTest\Integration\Adapter;

use DodLite\Adapter\AdapterInterface;
use DodLite\Adapter\AtomicFileAdapter;
use DodLite\Exceptions\NotFoundException;
use DodLite\Filter\TrueFilter;

function createAtomicFileAdapter(string $test, bool $useGlob = false): AdapterInterface
{
    return new AtomicFileAdapter(
        createDodTempDir('atomicFileAdapterTest-' . $test),
        useGlob: $useGlob,
    );
}

afterAll(function (): void {
    clearDodTempDir('atomicFileAdapterTest-');
});

test('Reading non-existing data throws exception', function (): void {
    $atomicFileAdapter = createAtomicFileAdapter('read-non-existing');
    $atomicFileAdapter->read('collection', 'key');
})->throws(NotFoundException::class);

test('Writing and Reading data works', function (): void {
    $atomicFileAdapter = createAtomicFileAdapter('write');

    $atomicFileAdapter->write('collection', 'key', ['data' => 'value']);
    $data = $atomicFileAdapter->read('collection', 'key');

    expect($data)->toBe(['data' => 'value']);
});

test('Deleting data works', function (): void {
    $atomicFileAdapter = createAtomicFileAdapter('delete');

    $atomicFileAdapter->write('collection', 'key', ['data' => 'value']);
    expect($atomicFileAdapter->has('collection', 'key'))->toBeTrue();
    expect($atomicFileAdapter->read('collection', 'key'))->toBe(['data' => 'value']);

    $atomicFileAdapter->delete('collection', 'key');
    expect($atomicFileAdapter->has('collection', 'key'))->toBeFalse();
});

test('readAll works', function (bool $useGlob): void {
    $atomicFileAdapter = createAtomicFileAdapter('readAll', $useGlob);

    $atomicFileAdapter->write('collection', 'key', ['data' => 'value']);
    $atomicFileAdapter->write('collection', 'key2', ['data' => 'value2']);

    $documents = iterator_to_array($atomicFileAdapter->readAll('collection', new TrueFilter()));
    expect($documents)
        ->toHaveKey('key')
        ->toHaveKey('key2');
})->with([false, true]);

test('readAll without data works', function (bool $useGlob): void {
    $atomicFileAdapter = createAtomicFileAdapter('readAll-empty', $useGlob);

    $documents = iterator_to_array($atomicFileAdapter->readAll('collection', new TrueFilter()));
    expect($documents)->toBe([]);
})->with([false, true]);

test('getAllCollectionNames works', function (bool $useGlob): void {
    $atomicFileAdapter = createAtomicFileAdapter('getAllCollectionNames', $useGlob);

    $atomicFileAdapter->write('collection', 'key', ['data' => 'value']);
    $atomicFileAdapter->write('collection2', 'key2', ['data' => 'value2']);

    $collectionNames = iterator_to_array($atomicFileAdapter->getAllCollectionNames());
    expect($collectionNames)->toContain('collection', 'collection2');
})->with([false, true]);

test('Consecutive writes: last-write-wins', function (bool $useGlob): void {
    $a = createAtomicFileAdapter('consecutive-last-wins', $useGlob);

    $a->write('c', 'k', ['n' => 1]);
    expect($a->read('c', 'k'))->toBe(['n' => 1]);

    $a->write('c', 'k', ['n' => 2]);
    expect($a->read('c', 'k'))->toBe(['n' => 2]);

    $a->write('c', 'k', ['n' => 3, 'x' => 'final']);
    expect($a->read('c', 'k'))->toBe(['n' => 3, 'x' => 'final']);
})->with([false, true]);

test('Consecutive writes with varying payload sizes (grow/shrink)', function (bool $useGlob): void {
    $a = createAtomicFileAdapter('consecutive-size-variance', $useGlob);

    // small
    $a->write('c', 'k', ['s' => 'a']);
    expect($a->read('c', 'k'))->toBe(['s' => 'a']);

    // larger
    $a->write('c', 'k', ['s' => str_repeat('b', 1024)]);
    expect($a->read('c', 'k'))->toBe(['s' => str_repeat('b', 1024)]);

    // shrink again
    $a->write('c', 'k', ['s' => 'c']);
    expect($a->read('c', 'k'))->toBe(['s' => 'c']);
})->with([false, true]);

test('Heavy consecutive writes: 100 overwrites end consistent', function (bool $useGlob): void {
    $a = createAtomicFileAdapter('consecutive-heavy', $useGlob);

    for ($i = 1; $i <= 100; $i++) {
        $a->write('c', 'k', ['i' => $i, 'p' => str_repeat((string)($i % 10), $i)]);
        // optional spot check mid-way
        if ($i % 25 === 0) {
            $mid = $a->read('c', 'k');
            expect($mid['i'])->toBe($i);
        }
    }

    $final = $a->read('c', 'k');
    expect($final['i'])->toBe(100);
    expect($final['p'])->toBe(str_repeat('0', 100)); // 100 % 10 = 0
})->with([false, true]);

test('Consecutive writes across collections are isolated', function (bool $useGlob): void {
    $a = createAtomicFileAdapter('consecutive-collections', $useGlob);

    $a->write('c1', 'k', ['v' => 1]);
    $a->write('c2', 'k', ['v' => 10]);

    $a->write('c1', 'k', ['v' => 2]);   // overwrite only in c1

    expect($a->read('c1', 'k'))->toBe(['v' => 2]);
    expect($a->read('c2', 'k'))->toBe(['v' => 10]);
})->with([false, true]);

test('Overwrite then delete leaves no residue', function (bool $useGlob): void {
    $a = createAtomicFileAdapter('overwrite-then-delete', $useGlob);

    $a->write('c', 'k', ['v' => 'a']);
    $a->write('c', 'k', ['v' => 'b']); // overwrite
    expect($a->read('c', 'k'))->toBe(['v' => 'b']);

    $a->delete('c', 'k');
    expect($a->has('c', 'k'))->toBeFalse();

    // Recreate after delete should work cleanly
    $a->write('c', 'k', ['v' => 'c']);
    expect($a->read('c', 'k'))->toBe(['v' => 'c']);
})->with([false, true]);
