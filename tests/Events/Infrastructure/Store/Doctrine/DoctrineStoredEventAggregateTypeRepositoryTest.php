<?php

namespace C201\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use C201\Ddd\Events\Testing\DomainEventTestTrait;
use C201\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateType;
use C201\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateTypeNotFoundException;
use C201\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateTypeRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Tightenco\Collect\Support\Collection;

class DoctrineStoredEventAggregateTypeRepositoryTest extends TestCase
{
    use ProphecyTrait;
    use \C201\Ddd\Doctrine\Testing\DoctrineTestTrait;
    use DomainEventTestTrait;
    use \C201\Ddd\Tests\Events\Infrastructure\Store\Doctrine\DoctrineEventStoreTestTrait;

    private DoctrineStoredEventAggregateTypeRepository $fixture;

    protected function setUp(): void
    {
        $this->initDoctrineTestTrait(DoctrineStoredEventAggregateType::class);
        $this->fixture = new DoctrineStoredEventAggregateTypeRepository($this->em->reveal());
    }

    public function testFindOneOrCreateThrowsExceptionIfThereIsMoreThanOneFreshlyCreatedMatch(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $freshlyCreatedType1 = new DoctrineStoredEventAggregateType(uniqid(), $event->aggregateType());
        $freshlyCreatedType2 = new DoctrineStoredEventAggregateType(uniqid(), $event->aggregateType());

        $classReflection = new \ReflectionClass(DoctrineStoredEventAggregateTypeRepository::class);
        $propertyReflection = $classReflection->getProperty('freshlyCreated');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this->fixture, Collection::make([$freshlyCreatedType1, $freshlyCreatedType2]));

        $this->expectExceptionObject(new \Exception("Found more than one aggregate type with name '{$freshlyCreatedType1->name()}'"));
        $this->fixture->findOneOrCreate($event);
    }

    public function testFindOneOrCreateReturnsFreshlyCreatedMatch(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $freshlyCreatedType = new DoctrineStoredEventAggregateType(uniqid(), $event->aggregateType());

        $classReflection = new \ReflectionClass(DoctrineStoredEventAggregateTypeRepository::class);
        $propertyReflection = $classReflection->getProperty('freshlyCreated');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this->fixture, Collection::make([$freshlyCreatedType]));

        $this->repository->findOneBy(Argument::any())->shouldNotBeCalled();

        $this->assertSame($freshlyCreatedType, $this->fixture->findOneOrCreate($event));
    }

    public function testFindOneOrCreateReturnsResultFromRepository(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $aggregateType = new DoctrineStoredEventAggregateType(uniqid(), $event->aggregateType());
        $this->repository->findOneBy(['name' => $aggregateType->name()])->willReturn($aggregateType);
        $this->repository->findOneBy(['name' => $aggregateType->name()])->shouldBeCalledTimes(1);
        $this->assertSame($aggregateType, $this->fixture->findOneOrCreate($event));
    }

    public function testFindOneOrCreateCreatesPersistsAndReturnsNewAggregateType(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $this->repository->findOneBy(['name' => $event->aggregateType()])->willReturn(null);
        $this->em->persist(Argument::that(function (DoctrineStoredEventAggregateType $aggregateType) use ($event) {
            return $aggregateType->name() === $event->aggregateType();
        }))->shouldBeCalledTimes(1);
        $result = $this->fixture->findOneOrCreate($event);
        $this->assertEquals($result->name(), $event->aggregateType());
    }

    public function testFindOrCreateReturnsFreshlyCreatedAggregateTypeOnSecondCall(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $this->repository->findOneBy(['name' => $event->aggregateType()])->willReturn(null);
        $this->em->persist(Argument::that(function (DoctrineStoredEventAggregateType $aggregateType) use ($event) {
            return $aggregateType->name() === $event->aggregateType();
        }))->shouldBeCalledTimes(1);

        $this->repository->findOneBy(['name' => $event->aggregateType()])->shouldBeCalledTimes(1);
        $this->fixture->findOneOrCreate($event);
        $result = $this->fixture->findOneOrCreate($event);
        $this->assertEquals($result->name(), $event->aggregateType());
    }

    public function testFindOneThrowsExceptionIfThereIsMoreThanOneFreshlyCreatedMatch(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $freshlyCreatedType1 = new DoctrineStoredEventAggregateType(uniqid(), $event->aggregateType());
        $freshlyCreatedType2 = new DoctrineStoredEventAggregateType(uniqid(), $event->aggregateType());

        $classReflection = new \ReflectionClass(DoctrineStoredEventAggregateTypeRepository::class);
        $propertyReflection = $classReflection->getProperty('freshlyCreated');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this->fixture, Collection::make([$freshlyCreatedType1, $freshlyCreatedType2]));

        $this->expectExceptionObject(new \Exception("Found more than one aggregate type with name '{$freshlyCreatedType1->name()}'"));
        $this->fixture->findOne($event->aggregateType());
    }

    public function testFindOneReturnsFreshlyCreatedMatch(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $freshlyCreatedType = new DoctrineStoredEventAggregateType(uniqid(), $event->aggregateType());

        $classReflection = new \ReflectionClass(DoctrineStoredEventAggregateTypeRepository::class);
        $propertyReflection = $classReflection->getProperty('freshlyCreated');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this->fixture, Collection::make([$freshlyCreatedType]));

        $this->repository->findOneBy(Argument::any())->shouldNotBeCalled();

        $this->assertSame($freshlyCreatedType, $this->fixture->findOne($event->aggregateType()));
    }

    public function testFindOneReturnsResultFromRepository(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $aggregateType = new DoctrineStoredEventAggregateType(uniqid(), $event->aggregateType());
        $this->repository->findOneBy(['name' => $aggregateType->name()])->willReturn($aggregateType);
        $this->repository->findOneBy(['name' => $aggregateType->name()])->shouldBeCalledTimes(1);
        $this->assertSame($aggregateType, $this->fixture->findOne($event->aggregateType()));
    }

    public function testFindOneThrowsDoctrineStoredEventAggregateTypeNotFoundExceptionIfTypeIsNotFreshlyPersistedOrFoundInRepository(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $this->repository->findOneBy(['name' => $event->aggregateType()])->willReturn(null);
        $this->expectException(DoctrineStoredEventAggregateTypeNotFoundException::class);
        $this->fixture->findOne($event->aggregateType());
    }

    public function testClearFreshlyCreated(): void
    {
        $this->fixture->clearFreshlyCreated();
        $this->assertTrue(true);
    }
}
