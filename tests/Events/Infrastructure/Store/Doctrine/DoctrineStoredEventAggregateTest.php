<?php declare(strict_types=1);

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregate;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateType;
use PHPUnit\Framework\TestCase;

class DoctrineStoredEventAggregateTest extends TestCase
{
    public function testGettersReturnArgumentsPassedToConstructor() : void
    {
        $id = $this->givenADoctrineStoredEventAggregateId();
        $type = $this->givenADoctrineStoredEventAggregateType();
        $version = $this->givenADoctrineStoredEventAggregateVersion();
        $aggregate = new DoctrineStoredEventAggregate($id, $type, $version);
        self::assertEquals($id, $aggregate->id());
        self::assertSame($type, $aggregate->aggregateType());
        self::assertEquals($version, $aggregate->version());
    }

    public function testIncrementVersion() : void
    {
        $startingVersion = $this->givenADoctrineStoredEventAggregateVersion();
        $aggregate = new DoctrineStoredEventAggregate(
            $this->givenADoctrineStoredEventAggregateId(),
            $this->givenADoctrineStoredEventAggregateType(),
            $startingVersion
        );
        self::assertEquals($startingVersion, $aggregate->version());
        $aggregate->incrementVersion();
        self::assertEquals($startingVersion + 1, $aggregate->version());
    }

    private function givenADoctrineStoredEventAggregateId() : string
    {
        return \uniqid();
    }

    private function givenADoctrineStoredEventAggregateType() : DoctrineStoredEventAggregateType
    {
        return new DoctrineStoredEventAggregateType(\uniqid(), \uniqid());
    }

    private function givenADoctrineStoredEventAggregateVersion() : int
    {
        return \random_int(1, 1000);
    }

    public function testPrePersistSetsCreatedTsAndUpdatedTs() : void
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

        self::assertNull($createdTsReflection->getValue($aggregate));
        self::assertNull($updatedTsReflection->getValue($aggregate));
        $aggregate->prePersist();
        self::assertNotNull($createdTsReflection->getValue($aggregate));
        self::assertNotNull($updatedTsReflection->getValue($aggregate));
    }

    public function testPreUpdateSetsUpdatedTsButNotCreatedTs() : void
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

        self::assertSame($startingCreatedTs, $createdTsReflection->getValue($aggregate));
        self::assertNotSame($startingUpdatedTs, $updatedTsReflection->getValue($aggregate));
        self::assertNotNull($updatedTsReflection->getValue($aggregate));
    }
}
