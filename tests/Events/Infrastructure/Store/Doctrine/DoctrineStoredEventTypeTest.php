<?php

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventType;
use PHPUnit\Framework\TestCase;

class DoctrineStoredEventTypeTest extends TestCase
{
    public function testGettersReturnArgumentsPassedToConstructor(): void
    {
        $id = $this->givenAnEventTypeId();
        $name = $this->givenAnEventTypeName();
        $eventType = new DoctrineStoredEventType($id, $name);
        $this->assertSame($id, $eventType->id());
        $this->assertSame($name, $eventType->name());
    }

    private function givenAnEventTypeId(): string
    {
        return uniqid();
    }

    private function givenAnEventTypeName(): string
    {
        return uniqid();
    }

    public function testPrePersistSetsCreatedTs(): void
    {
        $eventType = new DoctrineStoredEventType($this->givenAnEventTypeId(), $this->givenAnEventTypeName());

        $classReflection = new \ReflectionClass(DoctrineStoredEventType::class);
        $createdTsReflection = $classReflection->getProperty('createdTs');
        $createdTsReflection->setAccessible(true);

        $this->assertNull($createdTsReflection->getValue($eventType));
        $eventType->prePersist();
        $this->assertNotNull($createdTsReflection->getValue($eventType));
    }
}
