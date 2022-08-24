<?php declare(strict_types=1);

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Domain\AbstractDomainEvent;
use Becklyn\Ddd\Events\Domain\EventId;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 *
 * @since  2019-08-28
 */
class DoctrineEventStoreTestEvent extends AbstractDomainEvent
{
    public function __construct(
        EventId $id,
        \DateTimeImmutable $raisedTs,
        private DoctrineEventStoreTestAggregateId $aggregateId,
    ) {
        parent::__construct($id, $raisedTs);
    }

    public function aggregateId() : DoctrineEventStoreTestAggregateId
    {
        return $this->aggregateId;
    }

    public function aggregateType() : string
    {
        return 'DoctrineEventStoreTestAggregate';
    }
}
