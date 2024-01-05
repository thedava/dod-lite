<?php
declare(strict_types=1);

namespace DodTest\Unit\DodLite\Documents;

use DodLite\Documents\Document;

test('Update document content works', function () {
    $document = new Document('id', ['foo' => 'bar']);
    $document->updateContent(['foo' => 'baz']);

    expect($document->getContent())->toBe(['foo' => 'baz']);
});

test('Example from readme works', function () {
    $document = new Document('Concepts', [
        'file'        => '03-Concepts.md',
        'headline'    => 'Concepts',
        'description' => 'Basic principles',
    ]);

    // Update content manually
    $content = $document->getContent();
    $content['description'] = 'DodLite uses collections and documents to store data';
    $document->setContent($content);

    // Update content via helper method (array_replace_recursive)
    $document->updateContent([
        'headline' => 'Concepts and Basic principles',
    ]);

    expect($document->getContent())->toBe([
        'file'        => '03-Concepts.md',
        'headline'    => 'Concepts and Basic principles',
        'description' => 'DodLite uses collections and documents to store data',
    ]);
});
