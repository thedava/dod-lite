<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use DateTimeImmutable;
use DodLite\Adapter\AdapterInterface;
use DodLite\Adapter\MetaAdapterInterface;
use DodLite\DodException;
use DodLite\Exceptions\Adapter\AdapterInitializationFailedException;
use DodLite\Exceptions\Adapter\LockAdapterException;

class LockAdapter extends AbstractMetaAdapter implements AdapterInterface, MetaAdapterInterface
{
    private const FEATURE = 'lock';
    public const TIMEOUT = 5;
    public const MAX_TRIES = 10;

    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly int              $timeout = self::TIMEOUT,
        private readonly int              $maxTries = self::MAX_TRIES,
    )
    {
        parent::__construct($adapter);

        if ($this->timeout < 1) {
            throw new AdapterInitializationFailedException('Timeout must be at least 1 second');
        }
    }

    private function getLock(string $collection): void
    {
        $now = new DateTimeImmutable();

        $this->adapter->write(
            $this->getMetaCollectionName($collection, self::FEATURE),
            'lock',
            [
                'locked'     => true,
                'pid'        => getmypid(),
                'lockTime'   => $now->format('c'),
                'expireTime' => $now->modify(sprintf('+%d seconds', $this->timeout))->format('c'),
            ]
        );
    }

    private function releaseLock(string $collection): void
    {
        $metaCollection = $this->getMetaCollectionName($collection, self::FEATURE);

        try {
//            $this->adapter->write(
//                $metaCollection,
//                'lock',
//                [
//                    'locked' => false,
//                    'pid'    => getmypid(),
//                ]
//            );

            $this->adapter->delete($metaCollection, 'lock');
        } catch (DodException $e) {
            throw new LockAdapterException('release', $collection, previous: $e);
        }
    }

    private function hasLock(string $collection): bool
    {
        $metaCollection = $this->getMetaCollectionName($collection, self::FEATURE);

        if (!$this->has($metaCollection, 'lock')) {
            return false;
        }

        $lock = $this->read($metaCollection, 'lock');

        // Check if lock is disabled
        if ($lock['locked'] === false) {
            return false;
        }

        // Check if lock is expired by expiration time
        if (!isset($lock['expireTime'])) {
            return false;
        }
        $now = new DateTimeImmutable();
        $expireTime = new DateTimeImmutable($lock['expireTime']);
        if ($expireTime < $now) {
            return false;
        }

        // Fallback: Check if lock is expired by lock time and timeout
        if (!isset($lock['lockTime'])) {
            return false;
        }
        $lockTime = new DateTimeImmutable($lock['lockTime']);
        $diff = $now->getTimestamp() - $lockTime->getTimestamp();

        return ($diff <= $this->timeout);
    }

    private function waitForLock(string $collection): void
    {
        $tries = 0;
        while ($this->hasLock($collection)) {
            if ($tries >= $this->maxTries) {
                throw new LockAdapterException('get', $collection, previous: new DodException('Max tries exceeded'));
            }
            $tries++;
            sleep(1);
        }
    }

    public function write(string $collection, int|string $id, array $data): void
    {
        $this->waitForLock($collection);

        try {
            $this->getLock($collection);
            parent::write($collection, $id, $data);
        } finally {
            $this->releaseLock($collection);
        }
    }

    public function delete(string $collection, int|string $id): void
    {
        $this->waitForLock($collection);

        try {
            $this->getLock($collection);
            parent::delete($collection, $id);
        } finally {
            $this->releaseLock($collection);
        }
    }
}
