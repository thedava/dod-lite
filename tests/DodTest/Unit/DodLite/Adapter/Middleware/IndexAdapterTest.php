<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Adapter\Middleware;

use Closure;
use DodLite\Adapter\AdapterInterface;
use DodLite\Adapter\MemoryAdapter;
use DodLite\Adapter\Middleware\Index\PreFilter\CallbackIndexPreFilter;
use DodLite\Adapter\Middleware\Index\ValueExtractor\SimpleIndexValueExtractor;
use DodLite\Adapter\Middleware\IndexAdapter;
use DodLite\Documents\DocumentInterface;
use DodLite\Filter\TrueFilter;
use Exception;
use PHPUnit\Framework\AssertionFailedError;

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

test('Disposing works', function () {
    $memory = new MemoryAdapter();
    $indexAdapter = new IndexAdapter($memory, 'meta-test');

    // Force creation of empty index
    expect($indexAdapter->has('test', 1))->toBeFalse();
    expect($indexAdapter->has('test-two', 1))->toBeFalse();

    // Add keys directly to memory (bypassing index update)
    $memory->write('test', 1, ['foo' => 'bar']);
    $memory->write('test-two', 1, ['foo' => 'bar']);

    // Check that the index still doesn't know these documents
    expect($indexAdapter->has('test', 1))->toBeFalse();
    expect($indexAdapter->has('test-two', 1))->toBeFalse();

    $indexAdapter->dispose();

    expect($indexAdapter->has('test', 1))->toBeTrue(); // Index for "test" will be recreated here
    expect($indexAdapter->has('test-two', 1))->toBeTrue(); // Index for "test-two" will be recreated here
});

test('Refreshing works', function () {
    $memory = new MemoryAdapter();
    $indexAdapter = new IndexAdapter($memory, 'meta-test');

    // Force creation of empty index
    expect($indexAdapter->has('test', 1))->toBeFalse();
    expect($indexAdapter->has('test-two', 1))->toBeFalse();

    // Add keys directly to memory (bypassing index update)
    $memory->write('test', 1, ['foo' => 'bar']);
    $memory->write('test-two', 1, ['foo' => 'bar']);

    // Check that the index still doesn't know these documents
    expect($indexAdapter->has('test', 1))->toBeFalse();
    expect($indexAdapter->has('test-two', 1))->toBeFalse();

    $indexAdapter->refresh(); // All indices will be recreated here

    expect($indexAdapter->has('test', 1))->toBeTrue();
    expect($indexAdapter->has('test-two', 1))->toBeTrue();
});

$createIndexAdapterForValueExtraction = function (AdapterInterface $adapter): IndexAdapter {
    $indexAdapter = new IndexAdapter($adapter, 'meta-test', new SimpleIndexValueExtractor(['type']));

    $indexAdapter->write('test', 1, ['type' => 'foo', 'group' => 1]);
    $indexAdapter->write('test', 2, ['type' => 'bar', 'group' => 1]);
    $indexAdapter->write('test', 3, ['type' => 'baz', 'group' => 1]);

    $indexAdapter->write('test', 4, ['type' => 'foo', 'group' => 2]);
    $indexAdapter->write('test', 5, ['type' => 'bar', 'group' => 2]);
    $indexAdapter->write('test', 6, ['type' => 'baz', 'group' => 2]);

    $indexAdapter->write('test', 7, ['type' => 'foo', 'group' => 3]);
    $indexAdapter->write('test', 8, ['type' => 'bar', 'group' => 3]);
    $indexAdapter->write('test', 9, ['type' => 'baz', 'group' => 3]);

    return $indexAdapter;
};

test('Value extraction works', function () use ($createIndexAdapterForValueExtraction) {
    $indexAdapter = $createIndexAdapterForValueExtraction($memory = new MemoryAdapter());

    $indexData = $memory->read('meta-test', 'test.index');
    foreach ($indexData['ids'] as $data) {
        expect($data['extractedValues'])->toHaveKey('type', message: 'The type should be extracted');
        expect(count($data['extractedValues']))->toBe(1, message: 'Only the type should be extracted');
    }

    expect(count(iterator_to_array($indexAdapter->readAll('test', new TrueFilter()))))->toBe(9);
});

test('Pre-Filtering works', function () use ($createIndexAdapterForValueExtraction) {
    $storageAdapter = new class() extends MemoryAdapter {
        private ?Closure $onReadCallback = null;

        public function setOnReadCallback(?Closure $onReadCallback): void
        {
            $this->onReadCallback = $onReadCallback;
        }

        public function read(string $collection, int|string $id): array
        {
            if ($this->onReadCallback !== null) {
                call_user_func_array($this->onReadCallback, [$id]);
            }

            return parent::read($collection, $id);
        }
    };

    $indexAdapter = $createIndexAdapterForValueExtraction($storageAdapter);

    // Test if custom storageAdapter works as expected for this test
    $storageAdapter->setOnReadCallback(function () {
        // Every read should throw an exception
        throw new Exception('onReadCallbackTest-phpunit');
    });
    try {
        // Trigger readAll to force the storageAdapter to throw an exception
        iterator_to_array($indexAdapter->readAll('test', new TrueFilter()));
        expect(true)->toBeFalse('This should not be reached');
    } catch (AssertionFailedError $e) {
        throw $e;
    } catch (Exception $e) {
        expect($e->getMessage())->toBe('onReadCallbackTest-phpunit');
    }

    // Force storage to throw errors if any entry with type !== "foo" is called
    $storageAdapter->setOnReadCallback(function ($id) {
        expect($id)->not()->toBeIn([2, 3, 5, 6, 8, 9], message: 'The storageAdapter should only be called for entries with type "foo"');
    });
    $generator = $indexAdapter->readAll(
        'test',
        new CallbackIndexPreFilter(
            fn(DocumentInterface $document) => true,
            fn(array $extractedValues) => $extractedValues['type'] === 'foo'
        )
    );
    foreach ($generator as $data) {
        expect($data)
            ->toHaveKey('type')
            ->toHaveKey('group');
        expect($data['type'])->toBe('foo');
    }

    // Force storage to throw errors if any entry with type !== "bar" is called
    $storageAdapter->setOnReadCallback(function ($id) {
        expect($id)->not()->toBeIn([1, 3, 4, 6, 7, 9], message: 'The storageAdapter should only be called for entries with type "bar"');
    });
    $generator = $indexAdapter->readAll(
        'test',
        new CallbackIndexPreFilter(
            fn(DocumentInterface $document) => true,
            fn(array $extractedValues) => $extractedValues['type'] === 'bar'
        )

    );
    foreach ($generator as $data) {
        expect($data)
            ->toHaveKey('type')
            ->toHaveKey('group');
        expect($data['type'])->toBe('bar');
    }
});
