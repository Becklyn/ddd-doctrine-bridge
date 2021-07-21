<?php

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Ramsey\Uuid\Uuid;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 * @since  2019-08-28
 */
trait DoctrineEventStoreTestTrait
{
    public function givenAnAggregateId(): DoctrineEventStoreTestAggregateId
    {
        return DoctrineEventStoreTestAggregateId::fromString(Uuid::uuid4());
    }
}
