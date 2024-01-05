# Utilities

DodLite comes with some utilities

## Synchronizer

The most basic util is the `\DodLite\Synchronizer`. It can be used to fully synchronize two document managers.
This means that all collections and documents are copied from the source to the target document manager.
All existing documents within the target document manager will be removed during the synchronization if not disabled.
The `.meta` collection will be ignored by the synchronization.

```php
// Default behaviour: Create an exact copy of the source document manager (target document manager will delete documents that are not present in the source document manager)
$synchronizer = new \DodLite\Synchronizer($sourceDocumentManager, $targetDocumentManager);
$synchronizer->synchronize();

// Disable removal of existing documents (target manager keeps all documents, even those that are not present in the source document manager)
$synchronizer = new \DodLite\Synchronizer($sourceDocumentManager, $targetDocumentManager);
$synchronizer->synchronize(synchronizeDeletes: false);

// Exclude some collections entirely from the synchronization (this collection will be skipped entirely. No documents will be transferred and no documents will be deleted)
$synchronizer = new \DodLite\Synchronizer($sourceDocumentManager, $targetDocumentManager, excludeCollections: ['sessions']);
$synchronizer->synchronize();

// Only synchronize given collections and ignore all others
$synchronizer = new \DodLite\Synchronizer($sourceDocumentManager, $targetDocumentManager, includeCollections: ['users']);
$synchronizer->synchronize();
```
