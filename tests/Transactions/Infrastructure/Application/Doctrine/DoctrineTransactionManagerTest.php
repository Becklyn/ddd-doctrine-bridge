<?php

namespace Becklyn\Ddd\Tests\Transactions\Infrastructure\Application\Doctrine;

use Becklyn\Ddd\Events\Application\EventManager;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineEventStore;
use Becklyn\Ddd\Transactions\Infrastructure\Application\Doctrine\DoctrineTransactionManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class DoctrineTransactionManagerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|EntityManagerInterface $entityManager;
    private ObjectProphecy|EventManager $eventManager;
    private ObjectProphecy|DoctrineEventStore $eventStore;

    private DoctrineTransactionManager $fixture;

    public function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->eventManager = $this->prophesize(EventManager::class);
        $this->eventStore = $this->prophesize(DoctrineEventStore::class);
        $this->fixture = new DoctrineTransactionManager($this->entityManager->reveal(), $this->eventManager->reveal(), $this->eventStore->reveal());
    }

    public function testCommitSuccess()
    {
        $this->entityManager->flush()->shouldBeCalledTimes(1);
        $this->eventStore->clearFreshlyCreated()->shouldBeCalledTimes(1);
        $this->entityManager->clear()->shouldBeCalledTimes(1);
        $this->eventManager->flush()->shouldBeCalledTimes(1);
        $this->fixture->commit();
    }

    public function testCommitFail()
    {
        $this->entityManager->flush()->shouldBeCalledTimes(1);
        $exception = new \Exception();
        $this->entityManager->flush()->willThrow($exception);
        $this->eventStore->clearFreshlyCreated()->shouldBeCalledTimes(1);
        $this->entityManager->clear()->shouldBeCalledTimes(1);
        $this->eventManager->clear()->shouldBeCalledTimes(1);
        $this->expectExceptionObject($exception);
        $this->fixture->commit();
    }

    public function testRollback()
    {
        $this->eventStore->clearFreshlyCreated()->shouldBeCalledTimes(1);
        $this->entityManager->clear()->shouldBeCalledTimes(1);
        $this->eventManager->clear()->shouldBeCalledTimes(1);
        $this->fixture->rollback();
    }
}
