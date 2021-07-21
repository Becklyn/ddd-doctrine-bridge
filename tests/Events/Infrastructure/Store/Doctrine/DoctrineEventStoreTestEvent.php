<?php

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Domain\AbstractDomainEvent;
use Becklyn\Ddd\Events\Domain\EventId;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 * @since  2019-08-28
 */
class DoctrineEventStoreTestEvent extends AbstractDomainEvent
{
    private DoctrineEventStoreTestAggregateId $aggregateId;

    public function __construct(EventId $id, \DateTimeImmutable $raisedTs, DoctrineEventStoreTestAggregateId $aggregateId)
    {
        parent::__construct($id, $raisedTs);
        $this->aggregateId = $aggregateId;
    }

    public function aggregateId(): DoctrineEventStoreTestAggregateId
    {
        return $this->aggregateId;
    }

    public function aggregateType(): string
    {
        return 'DoctrineEventStoreTestAggregate';
    }
}
