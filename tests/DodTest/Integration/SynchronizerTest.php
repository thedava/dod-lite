<?php
declare(strict_types=1);

namespace DodTest\Integration;

use DodLite\Adapter\MemoryAdapter;
use DodLite\DocumentManager;
use DodLite\Synchronizer;

test('Document synchronization works', function () {
    $sourceManager = new DocumentManager(new MemoryAdapter());
    $sourceManager->getCollection('pest')->writeData(1, ['pest1']);
    expect($sourceManager->getCollection('pest')->hasDocumentById(1))->toBeTrue();

    $targetManager = new DocumentManager(new MemoryAdapter());
    expect($targetManager->getCollection('pest')->hasDocumentById(1))->toBeFalse();

    $synchronizer = new Synchronizer($sourceManager, $targetManager);
    $synchronizer->synchronize();

    expect($targetManager->getCollection('pest')->hasDocumentById(1))->toBeTrue('Document #1 should have been synchronized to the target manager');
    expect($sourceManager->getCollection('pest')->hasDocumentById(1))->toBeTrue('Document #1 should still be in the source manager');
});

test('Delete synchronization works', function () {
    $sourceManager = new DocumentManager(new MemoryAdapter());
    $sourceManager->getCollection('pest')->writeData(1, ['pest1']);
    expect($sourceManager->getCollection('pest')->hasDocumentById(1))->toBeTrue();

    $targetManager = new DocumentManager(new MemoryAdapter());
    $targetManager->getCollection('pest')->writeData(2, ['pest2']);
    expect($targetManager->getCollection('pest')->hasDocumentById(2))->toBeTrue();

    $synchronizer = new Synchronizer($sourceManager, $targetManager);
    $synchronizer->synchronize();

    expect($targetManager->getCollection('pest')->hasDocumentById(1))->toBeTrue('Document #1 should have been synchronized to the target manager');
    expect($targetManager->getCollection('pest')->hasDocumentById(2))->toBeFalse('Document #2 should have been deleted from the target manager');
});

test('Delete synchronization will be skipped if requested', function () {
    $sourceManager = new DocumentManager(new MemoryAdapter());
    $sourceManager->getCollection('pest')->writeData(1, ['pest1']);
    expect($sourceManager->getCollection('pest')->hasDocumentById(1))->toBeTrue();

    $targetManager = new DocumentManager(new MemoryAdapter());
    $targetManager->getCollection('pest')->writeData(2, ['pest2']);
    expect($targetManager->getCollection('pest')->hasDocumentById(2))->toBeTrue();

    $synchronizer = new Synchronizer($sourceManager, $targetManager);
    $synchronizer->synchronize(synchronizeDeletes: false);

    expect($targetManager->getCollection('pest')->hasDocumentById(1))->toBeTrue('Document #1 should have been synchronized to the target manager');
    expect($targetManager->getCollection('pest')->hasDocumentById(2))->toBeTrue('Document #2 should still be in the target manager');
});

test('Custom collections will be skipped', function () {
    $sourceManager = new DocumentManager(new MemoryAdapter());
    $sourceManager->getCollection('pest')->writeData(1, ['pest']);
    $sourceManager->getCollection('custom')->writeData(2, ['custom']);

    $targetManager = new DocumentManager(new MemoryAdapter());

    $synchronizer = new Synchronizer($sourceManager, $targetManager, ['custom']);
    $synchronizer->synchronize();

    expect($targetManager->getCollection('pest')->hasDocumentById(1))->toBeTrue('Document #1 should have been synchronized to the target manager');
    expect($targetManager->getCollection('custom')->hasDocumentById(2))->toBeFalse('Document #2 should have been skipped');
});

test('Only custom collections will be synchronized', function () {
    $sourceManager = new DocumentManager(new MemoryAdapter());
    $sourceManager->getCollection('pest')->writeData(1, ['pest']);
    $sourceManager->getCollection('custom')->writeData(2, ['custom']);

    $targetManager = new DocumentManager(new MemoryAdapter());

    $synchronizer = new Synchronizer($sourceManager, $targetManager, includeCollections: ['custom']);
    $synchronizer->synchronize();

    expect($targetManager->getCollection('pest')->hasDocumentById(1))->toBeFalse('Document #1 should have been skipped');
    expect($targetManager->getCollection('custom')->hasDocumentById(2))->toBeTrue('Document #2 should have been synchronized to the target manager');
});
