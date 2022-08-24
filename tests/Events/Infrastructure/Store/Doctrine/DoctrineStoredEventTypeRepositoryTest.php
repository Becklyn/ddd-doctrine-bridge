<?php declare(strict_types=1);

namespace Becklyn\Ddd\Tests\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Doctrine\Testing\DoctrineTestTrait;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventType;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventTypeRepository;
use Becklyn\Ddd\Events\Testing\DomainEventTestTrait;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class DoctrineStoredEventTypeRepositoryTest extends TestCase
{
    use ProphecyTrait;
    use DoctrineTestTrait;
    use DomainEventTestTrait;
    use DoctrineEventStoreTestTrait;

    private DoctrineStoredEventTypeRepository $fixture;

    protected function setUp() : void
    {
        $this->initDoctrineTestTrait(DoctrineStoredEventType::class);
        $this->fixture = new DoctrineStoredEventTypeRepository($this->em->reveal());
    }

    public function testFindOneOrCreateThrowsExceptionIfThereIsMoreThanOneFreshlyCreatedMatch() : void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $freshlyCreatedType1 = new DoctrineStoredEventType(\uniqid(), \get_class($event));
        $freshlyCreatedType2 = new DoctrineStoredEventType(\uniqid(), \get_class($event));

        $classReflection = new \ReflectionClass(DoctrineStoredEventTypeRepository::class);
        $propertyReflection = $classReflection->getProperty('freshlyCreated');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this->fixture, Collection::make([$freshlyCreatedType1, $freshlyCreatedType2]));

        $this->expectExceptionObject(new \Exception("Found more than one event type with name '{$freshlyCreatedType1->name()}'"));
        $this->fixture->findOneOrCreate($event);
    }

    public function testFindOneOrCreateReturnsFreshlyCreatedMatch() : void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $freshlyCreatedType = new DoctrineStoredEventType(\uniqid(), \get_class($event));

        $classReflection = new \ReflectionClass(DoctrineStoredEventTypeRepository::class);
        $propertyReflection = $classReflection->getProperty('freshlyCreated');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this->fixture, Collection::make([$freshlyCreatedType]));

        $this->repository->findOneBy(Argument::any())->shouldNotBeCalled();

        self::assertSame($freshlyCreatedType, $this->fixture->findOneOrCreate($event));
    }

    public function testFindOneOrCreateReturnsResultFromRepository() : void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $eventType = new DoctrineStoredEventType(\uniqid(), \get_class($event));
        $this->repository->findOneBy(['name' => $eventType->name()])->willReturn($eventType);
        $this->repository->findOneBy(['name' => $eventType->name()])->shouldBeCalledTimes(1);
        self::assertSame($eventType, $this->fixture->findOneOrCreate($event));
    }

    public function testFindOneOrCreateCreatesPersistsAndReturnsNewEventType() : void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $this->repository->findOneBy(['name' => \get_class($event)])->willReturn(null);
        $this->em->persist(Argument::that(function (DoctrineStoredEventType $eventType) use ($event) {
            return $eventType->name() === \get_class($event);
        }))->shouldBeCalledTimes(1);
        $result = $this->fixture->findOneOrCreate($event);
        self::assertEquals($result->name(), \get_class($event));
    }

    public function testFindOrCreateReturnsFreshlyCreatedEventTypeOnSecondCall() : void
    {
        $event = new DoctrineEventStoreTestEvent($this->givenAnEventId(), $this->givenARaisedTs(), $this->givenAnAggregateId());
        $this->repository->findOneBy(['name' => \get_class($event)])->willReturn(null);
        $this->em->persist(Argument::that(function (DoctrineStoredEventType $eventType) use ($event) {
            return $eventType->name() === \get_class($event);
        }))->shouldBeCalledTimes(1);

        $this->repository->findOneBy(['name' => \get_class($event)])->shouldBeCalledTimes(1);
        $this->fixture->findOneOrCreate($event);
        $result = $this->fixture->findOneOrCreate($event);
        self::assertEquals($result->name(), \get_class($event));
    }

    public function testClearFreshlyCreated() : void
    {
        $this->fixture->clearFreshlyCreated();
        self::assertTrue(true);
    }
}
