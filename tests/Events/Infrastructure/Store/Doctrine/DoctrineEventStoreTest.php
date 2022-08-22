<?php

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Domain\AggregateEventStream;
use Becklyn\Ddd\Events\Domain\DomainEvent;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateType;
use Becklyn\Ddd\Events\Testing\DomainEventTestTrait;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineEventStore;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEvent;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregate;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateRepository;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventType;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventTypeRepository;
use Becklyn\Ddd\Identity\Domain\AggregateId;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;

class DoctrineEventStoreTest extends TestCase
{
    use ProphecyTrait;
    use DomainEventTestTrait;

    private ObjectProphecy|EntityManagerInterface $em;
    private ObjectProphecy|ObjectRepository $repository;
    private ObjectProphecy|DoctrineStoredEventAggregateRepository $aggregateRepository;
    private ObjectProphecy|DoctrineStoredEventTypeRepository $eventTypeRepository;
    private ObjectProphecy|SerializerInterface $serializer;

    private DoctrineEventStore $fixture;

    protected function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->repository = $this->prophesize(ObjectRepository::class);
        $this->em->getRepository(DoctrineStoredEvent::class)->willReturn($this->repository->reveal());
        $this->aggregateRepository = $this->prophesize(DoctrineStoredEventAggregateRepository::class);
        $this->eventTypeRepository = $this->prophesize(DoctrineStoredEventTypeRepository::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);

        $this->fixture = new DoctrineEventStore(
            $this->em->reveal(),
            $this->aggregateRepository->reveal(),
            $this->eventTypeRepository->reveal(),
            $this->serializer->reveal(),
            true
        );
    }

    public function testAppendPersistsStoredEvent(): void
    {
        $eventProphecy = $this->prophesize(DomainEvent::class);
        $eventProphecy->id()->willReturn($this->givenAnEventId());
        $eventProphecy->raisedTs()->willReturn($this->givenARaisedTs());
        $eventProphecy->correlationId()->willReturn($this->givenAnEventId());
        $eventProphecy->causationId()->willReturn($this->givenAnEventId());
        $event = $eventProphecy->reveal();

        $aggregate = $this->prophesize(DoctrineStoredEventAggregate::class);
        $incrementedVersion = rand(1, 1000);
        $aggregate->incrementVersion()->will(function () use ($incrementedVersion) {
            $this->version()->willReturn($incrementedVersion);
            return $this;
        });
        $this->aggregateRepository->findOneOrCreate($event)->willReturn($aggregate->reveal());

        $eventType = new DoctrineStoredEventType('foo', 'bar');
        $this->eventTypeRepository->findOneOrCreate($event)->willReturn($eventType);

        $serializedEvent = uniqid();
        $this->serializer->serialize($event, 'json')->willReturn($serializedEvent);

        $aggregate->incrementVersion()->shouldBeCalledTimes(1);
        $this->em->persist(Argument::that(function (DoctrineStoredEvent $storedEvent) use (
            $event,
            $aggregate,
            $incrementedVersion,
            $serializedEvent,
            $eventType
        ) {
            return $storedEvent->eventId() === $event->id()->asString() &&
                $storedEvent->raisedTs() === $event->raisedTs() &&
                $storedEvent->aggregate() === $aggregate->reveal() &&
                $storedEvent->eventType() === $eventType &&
                $storedEvent->version() === $incrementedVersion &&
                $storedEvent->correlationId() === $event->correlationId()->asString() &&
                $storedEvent->causationId() === $event->causationId()->asString() &&
                $storedEvent->version() === $incrementedVersion &&
                $storedEvent->data() === $serializedEvent;
        }))->shouldBeCalledTimes(1);

        $this->fixture->append($event);
    }

    public function testAppendDoesNothingIfStoreIsDisabled(): void
    {
        $this->fixture = new DoctrineEventStore(
            $this->em->reveal(),
            $this->aggregateRepository->reveal(),
            $this->eventTypeRepository->reveal(),
            $this->serializer->reveal(),
            false
        );

        $this->em->persist(Argument::any())->shouldNotBeCalled();

        $this->fixture->append($this->prophesize(DomainEvent::class)->reveal());
    }

    public function testClearFreshlyCreatedClearsAggregateAndEventTypeRepositories(): void
    {
        $this->aggregateRepository->clearFreshlyCreated()->shouldBeCalled();
        $this->eventTypeRepository->clearFreshlyCreated()->shouldBeCalled();
        $this->fixture->clearFreshlyCreated();
    }

    public function testClearFreshlyCreatedDoesNotClearAggregateAndEventTypeRepositoriesIfStoreIsDisabled(): void
    {
        $this->fixture = new DoctrineEventStore(
            $this->em->reveal(),
            $this->aggregateRepository->reveal(),
            $this->eventTypeRepository->reveal(),
            $this->serializer->reveal(),
            false
        );

        $this->aggregateRepository->clearFreshlyCreated()->shouldNotBeCalled();
        $this->eventTypeRepository->clearFreshlyCreated()->shouldNotBeCalled();
        $this->fixture->clearFreshlyCreated();
    }

    public function testGetAggregateStreamReturnsAnAggregateStreamWithEventsDeserializedFromDoctrine(): void
    {
        $aggregateId = $this->givenAnAggregateId();
        $doctrineStoredEvent = $this->givenADoctrineStoredEvent();
        $this->givenAQueryBuilderIsCreatedThatReturnsAllDoctrineStoredEventsForAggregate($aggregateId, [$doctrineStoredEvent]);
        $deserializedEvent = $this->givenTheSerializerDeserializesTheDoctrineStoredEvent($doctrineStoredEvent);

        $aggregateEventStream = $this->whenGetAggregateStreamIsExecuted($aggregateId);
        $this->thenAggregateEventStreamShouldContainTheAggregateId($aggregateEventStream, $aggregateId);
        $this->thenAggregateEventStreamShouldContainEvents($aggregateEventStream, [$deserializedEvent]);
    }

    private function givenAnAggregateId(): AggregateId
    {
        $aggregateId = $this->prophesize(AggregateId::class);
        $aggregateId->asString()->willReturn(Uuid::uuid4()->toString());
        $aggregateId->aggregateType()->willReturn(uniqid());
        return $aggregateId->reveal();
    }

    private function givenADoctrineStoredEvent(): DoctrineStoredEvent
    {
        $event = $this->prophesize(DoctrineStoredEvent::class);
        $event->data()->willReturn(uniqid());
        $eventType = $this->prophesize(DoctrineStoredEventType::class);
        $eventType->name()->willReturn(uniqid());
        $event->eventType()->willReturn($eventType->reveal());
        return $event->reveal();
    }

    private function givenAQueryBuilderIsCreatedThatReturnsAllDoctrineStoredEventsForAggregate(AggregateId $aggregateId, array $doctrineStoredEvents): void
    {
        $qb = $this->prophesize(QueryBuilder::class);
        $this->em->createQueryBuilder()->willReturn($qb->reveal());
        $qb->select('e')->willReturn($qb->reveal());
        $qb->from(DoctrineStoredEvent::class, 'e')->willReturn($qb->reveal());
        $qb->join(DoctrineStoredEventAggregate::class, 'a', 'WITH', 'e.aggregate = a.id')->willReturn($qb->reveal());
        $qb->join(DoctrineStoredEventAggregateType::class, 'at', 'WITH', 'a.aggregateType = at.id')->willReturn($qb->reveal());
        $qb->andWhere('a.id = :aggregateId')->willReturn($qb->reveal());
        $qb->andWhere('at.name = :aggregateType')->willReturn($qb->reveal());
        $qb->setParameter('aggregateId', $aggregateId->asString())->willReturn($qb->reveal());
        $qb->setParameter('aggregateType', $aggregateId->aggregateType())->willReturn($qb->reveal());
        $qb->addOrderBy('e.raisedTs', 'ASC')->willReturn($qb->reveal());
        $qb->addOrderBy('e.version', 'ASC')->willReturn($qb->reveal());

        $query = $this->prophesize(AbstractQuery::class);
        $qb->getQuery()->willReturn($query->reveal());
        $query->execute()->willReturn($doctrineStoredEvents);
    }

    private function givenTheSerializerDeserializesTheDoctrineStoredEvent(DoctrineStoredEvent $doctrineStoredEvent): DomainEvent
    {
        $domainEvent = $this->prophesize(DomainEvent::class);
        $this->serializer->deserialize($doctrineStoredEvent->data(), $doctrineStoredEvent->eventType()->name(), 'json')
            ->willReturn($domainEvent->reveal());
        return $domainEvent->reveal();
    }

    private function whenGetAggregateStreamIsExecuted(AggregateId $aggregateId): AggregateEventStream
    {
        return $this->fixture->getAggregateStream($aggregateId);
    }

    private function thenAggregateEventStreamShouldContainTheAggregateId(AggregateEventStream $aggregateEventStream, AggregateId $aggregateId): void
    {
        $this->assertSame($aggregateId, $aggregateEventStream->aggregateId());
    }

    private function thenAggregateEventStreamShouldContainEvents(AggregateEventStream $aggregateEventStream, array $events): void
    {
        $eventsFound = 0;
        foreach ($aggregateEventStream->events() as $eventFromStream) {
            foreach ($events as $event) {
                if ($eventFromStream === $event) {
                    $eventsFound++;
                    break;
                }
            }
        }

        $this->assertCount($eventsFound, $events);
    }

    public function testGetAggregateStreamReturnsAnAggregateStreamWithNoEventsIfQueryReturnsNoEvents(): void
    {
        $aggregateId = $this->givenAnAggregateId();
        $this->givenAQueryBuilderIsCreatedThatReturnsAllDoctrineStoredEventsForAggregate($aggregateId, []);

        $aggregateEventStream = $this->whenGetAggregateStreamIsExecuted($aggregateId);
        $this->thenAggregateEventStreamShouldContainTheAggregateId($aggregateEventStream, $aggregateId);
        $this->thenAggregateEventStreamShouldContainNoEvents($aggregateEventStream);
    }

    private function thenAggregateEventStreamShouldContainNoEvents(AggregateEventStream $aggregateEventStream): void
    {
        $this->assertEmpty($aggregateEventStream->events());
    }
}
