<?php declare(strict_types=1);

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateType;
use PHPUnit\Framework\TestCase;

class DoctrineStoredEventAggregateTypeTest extends TestCase
{
    public function testGettersReturnArgumentsPassedToConstructor() : void
    {
        $id = $this->givenAnAggregateTypeId();
        $name = $this->givenAnAggregateTypeName();
        $aggregateType = new DoctrineStoredEventAggregateType($id, $name);
        self::assertSame($id, $aggregateType->id());
        self::assertSame($name, $aggregateType->name());
    }

    private function givenAnAggregateTypeId() : string
    {
        return \uniqid();
    }

    private function givenAnAggregateTypeName() : string
    {
        return \uniqid();
    }

    public function testPrePersistSetsCreatedTs() : void
    {
        $aggregateType = new DoctrineStoredEventAggregateType($this->givenAnAggregateTypeId(), $this->givenAnAggregateTypeName());

        $classReflection = new \ReflectionClass(DoctrineStoredEventAggregateType::class);
        $createdTsReflection = $classReflection->getProperty('createdTs');
        $createdTsReflection->setAccessible(true);

        self::assertNull($createdTsReflection->getValue($aggregateType));
        $aggregateType->prePersist();
        self::assertNotNull($createdTsReflection->getValue($aggregateType));
    }
}
