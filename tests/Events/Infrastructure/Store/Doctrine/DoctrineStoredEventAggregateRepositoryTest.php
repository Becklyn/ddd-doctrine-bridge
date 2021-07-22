<?php

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Testing\DomainEventTestTrait;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregate;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateRepository;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateType;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregateTypeRepository;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class DoctrineStoredEventAggregateRepositoryTest extends TestCase
{
    use ProphecyTrait;
    use DomainEventTestTrait;
    use DoctrineEventStoreTestTrait;

    private ObjectProphecy|EntityManagerInterface $em;
    private ObjectProphecy|ObjectRepository $repository;
    private ObjectProphecy|DoctrineStoredEventAggregateTypeRepository $aggregateTypeRepository;

    private DoctrineStoredEventAggregateRepository $fixture;

    protected function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->repository = $this->prophesize(ObjectRepository::class);
        $this->em->getRepository(DoctrineStoredEventAggregate::class)->willReturn($this->repository->reveal());
        $this->aggregateTypeRepository = $this->prophesize(DoctrineStoredEventAggregateTypeRepository::class);

        $this->fixture = new DoctrineStoredEventAggregateRepository($this->em->reveal(), $this->aggregateTypeRepository->reveal());
    }

    public function testFindOneOrCreateThrowsExceptionIfMoreThanOneResultIsFoundInTheFreshlyCreatedCollection(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $aggregateType = new DoctrineStoredEventAggregateType('foo', $event->aggregateType());
        $this->aggregateTypeRepository->findOneOrCreate($event)->willReturn($aggregateType);

        $freshlyCreatedAggregate1 = new DoctrineStoredEventAggregate($event->aggregateId()->asString(), $aggregateType, 2);
        $freshlyCreatedAggregate2 = new DoctrineStoredEventAggregate($event->aggregateId()->asString(), $aggregateType, 3);

        $classReflection = new \ReflectionClass(DoctrineStoredEventAggregateRepository::class);
        $propertyReflection = $classReflection->getProperty('freshlyCreated');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this->fixture, Collection::make([$freshlyCreatedAggregate1, $freshlyCreatedAggregate2]));

        $this->expectExceptionObject(new \Exception("Found more than one aggregate with id '{$event->aggregateId()->asString()}}' for type '{$aggregateType->name()}'"));
        $this->fixture->findOneOrCreate($event);
    }

    public function testFindOneOrCreateReturnsResultFromFreshlyCreatedCollectionIfOneIsFound(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $aggregateType = new DoctrineStoredEventAggregateType('foo', $event->aggregateType());
        $this->aggregateTypeRepository->findOneOrCreate($event)->willReturn($aggregateType);

        $freshlyCreatedAggregate = new DoctrineStoredEventAggregate($event->aggregateId()->asString(), $aggregateType, 2);

        $classReflection = new \ReflectionClass(DoctrineStoredEventAggregateRepository::class);
        $propertyReflection = $classReflection->getProperty('freshlyCreated');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this->fixture, Collection::make([$freshlyCreatedAggregate]));

        $this->repository->findOneBy(Argument::any())->shouldNotBeCalled();

        $this->assertSame($freshlyCreatedAggregate, $this->fixture->findOneOrCreate($event));
    }

    public function testFindOneOrCreateCreatesNewAggregatePersistsAndReturnsItIfExistingOneIsNotFound(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $aggregateType = new DoctrineStoredEventAggregateType('foo', $event->aggregateType());
        $this->aggregateTypeRepository->findOneOrCreate($event)->willReturn($aggregateType);

        $this->repository->findOneBy(['id' => $event->aggregateId()->asString(), 'aggregateType' => $aggregateType->id()])->willReturn(null);

        $this->em->persist(Argument::that(function (DoctrineStoredEventAggregate $aggregate) use ($event, $aggregateType) {
            return $aggregate->aggregateType() === $aggregateType &&
                $aggregate->version() === 0 &&
                $aggregate->id() === $event->aggregateId()->asString();
        }))->shouldBeCalledTimes(1);

        $result = $this->fixture->findOneOrCreate($event);
        $this->assertSame($aggregateType, $result->aggregateType());
        $this->assertEquals(0, $result->version());
        $this->assertEquals($result->id(), $event->aggregateId()->asString());
    }

    public function testFindOneOrCreateReturnsFreshlyCreatedAggregateOnSecondCall(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $aggregateType = new DoctrineStoredEventAggregateType('foo', $event->aggregateType());
        $this->aggregateTypeRepository->findOneOrCreate($event)->willReturn($aggregateType);

        $this->repository->findOneBy(['id' => $event->aggregateId()->asString(), 'aggregateType' => $aggregateType->id()])->willReturn(null);
        $this->repository->findOneBy(['id' => $event->aggregateId()->asString(), 'aggregateType' => $aggregateType->id()])->shouldBeCalledTimes(1);
        $this->em->persist(Argument::any())->shouldBeCalledTimes(1);

        $this->fixture->findOneOrCreate($event);
        $result = $this->fixture->findOneOrCreate($event);
        $this->assertSame($aggregateType, $result->aggregateType());
        $this->assertEquals(0, $result->version());
        $this->assertEquals($result->id(), $event->aggregateId()->asString());
    }

    public function testFindOneOrCreateReturnsResultFromRepository(): void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $aggregateType = new DoctrineStoredEventAggregateType('foo', $event->aggregateType());
        $this->aggregateTypeRepository->findOneOrCreate($event)->willReturn($aggregateType);

        $aggregate = new DoctrineStoredEventAggregate($event->aggregateId()->asString(), $aggregateType, 2);
        $this->repository->findOneBy(['id' => $event->aggregateId()->asString(), 'aggregateType' => $aggregateType->id()])->willReturn($aggregate);

        $this->assertSame($aggregate, $this->fixture->findOneOrCreate($event));
    }

    public function testClearFreshlyCreatedClearsAggregateTypeRepository(): void
    {
        $this->aggregateTypeRepository->clearFreshlyCreated()->shouldBeCalled();
        $this->fixture->clearFreshlyCreated();
    }
}
