<?php
declare(strict_types=1);

namespace DodLite\Adapter\Middleware;

use DodLite\Adapter\AdapterInterface;
use DodLite\Adapter\MetaAdapterInterface;

abstract class AbstractMetaAdapter extends PassThroughAdapter implements MetaAdapterInterface, AdapterInterface
{
    protected function getMetaCollectionName(string $originCollection, string $feature): string
    {
        return sprintf('%s.%s', $originCollection, $feature);
    }
}
