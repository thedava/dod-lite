# Exceptions

DodLite employs a consistent error handling system to ensure that all encountered errors are easy to identify and manage. Every error thrown by the library is either an
instance of `DodException` or a derivative of it. This allows you to efficiently catch and respond to errors. All errors contain the original exception as previous
exception if there is one.

## DodExceptions

Here is the list of the exception classes that are directly derived from `\DodLite\DodException`:

### AlreadyExistsException

`\DodLite\Exceptions\AlreadyExistsException`<br>
Thrown exclusively by the `moveDocument` functionality of the DocumentManager. Indicates that the target collection already contains a document with the same identifier.

### DeleteFailedException

`\DodLite\Exceptions\DeleteFailedException`<br>
Thrown when a document could not be deleted.

### NotFoundException

`\DodLite\Exceptions\NotFoundException`<br>
Thrown when a requested document is not found.

### ReadOnlyException

`\DodLite\Exceptions\ReadOnlyException`<br>
Thrown when a modification operation (like read or write) is attempted on a read-only adapter. This exception is currently exclusively thrown by the `ReadOnlyAdapter` and
only if configured to do so. It derives from `WriteFailedException`.

### WriteFailedException

`\DodLite\Exceptions\WriteFailedException`<br>
Thrown when a document could not be written.

## Adapter specific exceptions

Here is the list of adapter specific exception classes that are derived from `\DodLite\Exceptions\Adapter\DodAdapterException` (which itself is derived from `\DodLite\DodException`):

### AdapterInitializationFailedException

`\DodLite\Exceptions\Adapter\AdapterInitializationFailedException`<br>
Thrown when an adapter could not be initialized.

### LockAdapterException

`\DodLite\Exceptions\Adapter\LockAdapterException`<br>
Thrown when a lock could not be acquired. This exception is exclusively thrown by the `LockAdapter`.

### ReplicationFailedException

`\DodLite\Exceptions\Adapter\ReplicationFailedException`<br>
Thrown when a replication failed. This exception is exclusively thrown by the `ReplicateAdapter` and only if the operation on the replicated adapter failed. If the operation
failed on the main adapter regular exceptions like `WriteFailedException` will be thrown.
