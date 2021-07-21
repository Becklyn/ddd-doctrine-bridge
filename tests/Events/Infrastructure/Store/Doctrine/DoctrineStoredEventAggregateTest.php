<?php

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregate;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateType;
use PHPUnit\Framework\TestCase;

class DoctrineStoredEventAggregateTest extends TestCase
{
    public function testGettersReturnArgumentsPassedToConstructor(): void
    {
        $id = $this->givenADoctrineStoredEventAggregateId();
        $type = $this->givenADoctrineStoredEventAggregateType();
        $version = $this->givenADoctrineStoredEventAggregateVersion();
        $aggregate = new DoctrineStoredEventAggregate($id, $type, $version);
        $this->assertEquals($id, $aggregate->id());
        $this->assertSame($type, $aggregate->aggregateType());
        $this->assertEquals($version, $aggregate->version());
    }

    public function testIncrementVersion(): void
    {
        $startingVersion = $this->givenADoctrineStoredEventAggregateVersion();
        $aggregate = new DoctrineStoredEventAggregate(
            $this->givenADoctrineStoredEventAggregateId(),
            $this->givenADoctrineStoredEventAggregateType(),
            $startingVersion
        );
        $this->assertEquals($startingVersion, $aggregate->version());
        $aggregate->incrementVersion();
        $this->assertEquals($startingVersion + 1, $aggregate->version());
    }

    private function givenADoctrineStoredEventAggregateId(): string
    {
        return uniqid();
    }

    private function givenADoctrineStoredEventAggregateType(): DoctrineStoredEventAggregateType
    {
        return new DoctrineStoredEventAggregateType(uniqid(), uniqid());
    }

    private function givenADoctrineStoredEventAggregateVersion(): int
    {
        return random_int(1, 1000);
    }

    public function testPrePersistSetsCreatedTsAndUpdatedTs(): void
    {
        $aggregate = new DoctrineStoredEventAggregate(
            $this->givenADoctrineStoredEventAggregateId(),
            $this->givenADoctrineStoredEventAggregateType(),
            $this->givenADoctrineStoredEventAggregateVersion()
        );

        $classReflection = new \ReflectionClass(DoctrineStoredEventAggregate::class);
        $createdTsReflection = $classReflection->getProperty('createdTs');
        $createdTsReflection->setAccessible(true);
        $updatedTsReflection = $classReflection->getProperty('updatedTs');
        $updatedTsReflection->setAccessible(true);

        $this->assertNull($createdTsReflection->getValue($aggregate));
        $this->assertNull($updatedTsReflection->getValue($aggregate));
        $aggregate->prePersist();
        $this->assertNotNull($createdTsReflection->getValue($aggregate));
        $this->assertNotNull($updatedTsReflection->getValue($aggregate));
    }

    public function testPreUpdateSetsUpdatedTsButNotCreatedTs(): void
    {
        $aggregate = new DoctrineStoredEventAggregate(
            $this->givenADoctrineStoredEventAggregateId(),
            $this->givenADoctrineStoredEventAggregateType(),
            $this->givenADoctrineStoredEventAggregateVersion()
        );
        $startingCreatedTs = new \DateTimeImmutable();
        $startingUpdatedTs = new \DateTimeImmutable();

        $classReflection = new \ReflectionClass(DoctrineStoredEventAggregate::class);
        $createdTsReflection = $classReflection->getProperty('createdTs');
        $createdTsReflection->setAccessible(true);
        $createdTsReflection->setValue($aggregate, $startingCreatedTs);
        $updatedTsReflection = $classReflection->getProperty('updatedTs');
        $updatedTsReflection->setAccessible(true);
        $updatedTsReflection->setValue($aggregate, $startingUpdatedTs);

        $aggregate->preUpdate();

        $this->assertSame($startingCreatedTs, $createdTsReflection->getValue($aggregate));
        $this->assertNotSame($startingUpdatedTs, $updatedTsReflection->getValue($aggregate));
        $this->assertNotNull($updatedTsReflection->getValue($aggregate));
    }
}
