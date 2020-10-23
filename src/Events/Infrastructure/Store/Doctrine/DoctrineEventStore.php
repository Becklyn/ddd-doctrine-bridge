<?php

namespace C201\Ddd\Events\Infrastructure\Store\Doctrine;

use C201\Ddd\Events\Domain\AggregateEventStream;
use C201\Ddd\Events\Domain\DomainEvent;
use C201\Ddd\Events\Domain\EventStore;
use C201\Ddd\Identity\Domain\AggregateId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Tightenco\Collect\Support\Collection;

/**
 * @author Marko Vujnovic <mv@201created.de>
 * @since  2019-08-21
 */
class DoctrineEventStore implements EventStore
{
    private EntityManagerInterface $em;

    private DoctrineStoredEventAggregateRepository $aggregateRepository;

    private DoctrineStoredEventTypeRepository $eventTypeRepository;

    private SerializerInterface $serializer;

    private bool $isEnabled;

    public function __construct(
        EntityManagerInterface $em,
        DoctrineStoredEventAggregateRepository $aggregateRepository,
        DoctrineStoredEventTypeRepository $eventTypeRepository,
        SerializerInterface $serializer,
        bool $isEnabled
    ) {
        $this->em = $em;
        $this->aggregateRepository = $aggregateRepository;
        $this->eventTypeRepository = $eventTypeRepository;
        $this->serializer = $serializer;
        $this->isEnabled = $isEnabled;
    }

    public function append(DomainEvent $event): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $aggregate = $this->aggregateRepository->findOneOrCreate($event);
        $eventType = $this->eventTypeRepository->findOneOrCreate($event);

        $data = $this->serializer->serialize($event, 'json');

        $aggregate->incrementVersion();
        $storedEvent = new DoctrineStoredEvent($event->id()->asString(), $aggregate, $aggregate->version(), $eventType, $event->raisedTs(), $data);
        $this->em->persist($storedEvent);
    }

    public function getAggregateStream(AggregateId $aggregateId): AggregateEventStream
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')
            ->from(DoctrineStoredEvent::class, 'e')
            ->join(DoctrineStoredEventAggregate::class, 'a', 'WITH', 'e.aggregate = a.id')
            ->join(DoctrineStoredEventAggregateType::class, 'at', 'WITH', 'a.aggregateType = at.id')
            ->andWhere('a.id = :aggregateId')
            ->andWhere('at.name = :aggregateType')
            ->setParameter('aggregateId', $aggregateId->asString())
            ->setParameter('aggregateType', $aggregateId->aggregateType())
            ->addOrderBy('e.raisedTs', 'ASC')
            ->addOrderBy('e.version', 'ASC');

        $storedEvents = Collection::make($qb->getQuery()->execute())
            ->map(fn(DoctrineStoredEvent $storedEvent) => $this->serializer->deserialize($storedEvent->data(), $storedEvent->eventType()->name(), 'json'));

        return new AggregateEventStream($aggregateId, $storedEvents);
    }

    public function clearFreshlyCreated(): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $this->aggregateRepository->clearFreshlyCreated();
        $this->eventTypeRepository->clearFreshlyCreated();
    }
}
