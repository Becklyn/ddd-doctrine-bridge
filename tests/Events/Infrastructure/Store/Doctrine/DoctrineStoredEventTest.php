<?php

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEvent;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregate;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateType;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventType;
use PHPUnit\Framework\TestCase;

class DoctrineStoredEventTest extends TestCase
{
    public function testGettersReturnArgumentsPassedToConstructor(): void
    {
        $eventId = $this->givenAnEventId();
        $aggregate = $this->givenADoctrineStoredEventAggregate();
        $version = $this->givenAnAggregateVersion();
        $eventType = $this->givenADoctrineStoredEventType();
        $raisedTs = $this->givenARaisedTs();
        $data = $this->givenSerializedEventData();
        $storedEvent = new DoctrineStoredEvent($eventId, $aggregate, $version, $eventType, $raisedTs, $data);
        $this->assertSame($eventId, $storedEvent->eventId());
        $this->assertSame($aggregate, $storedEvent->aggregate());
        $this->assertSame($version, $storedEvent->version());
        $this->assertSame($eventType, $storedEvent->eventType());
        $this->assertSame($raisedTs, $storedEvent->raisedTs());
        $this->assertSame($data, $storedEvent->data());
    }

    private function givenAnEventId(): string
    {
        return uniqid();
    }

    private function givenADoctrineStoredEventAggregate(): DoctrineStoredEventAggregate
    {
        return new DoctrineStoredEventAggregate(uniqid(), new DoctrineStoredEventAggregateType(uniqid(), uniqid()));
    }

    private function givenAnAggregateVersion(): int
    {
        return rand(1, 1000);
    }

    private function givenADoctrineStoredEventType(): DoctrineStoredEventType
    {
        return new DoctrineStoredEventType(uniqid(), uniqid());
    }

    private function givenARaisedTs(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    private function givenSerializedEventData(): string
    {
        return uniqid();
    }

    public function testPrePersistSetsCreatedTs(): void
    {
        $storedEvent = new DoctrineStoredEvent(
            $this->givenAnEventId(),
            $this->givenADoctrineStoredEventAggregate(),
            $this->givenAnAggregateVersion(),
            $this->givenADoctrineStoredEventType(),
            $this->givenARaisedTs(),
            $this->givenSerializedEventData()
        );

        $classReflection = new \ReflectionClass(DoctrineStoredEvent::class);
        $propertyReflection = $classReflection->getProperty('createdTs');
        $propertyReflection->setAccessible(true);
        $propertyReflection->getValue($storedEvent);

        $this->assertNull($propertyReflection->getValue($storedEvent));
        $storedEvent->prePersist();
        $this->assertNotNull($propertyReflection->getValue($storedEvent));
    }
}
