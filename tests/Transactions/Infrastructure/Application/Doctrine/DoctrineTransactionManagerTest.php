<?php

namespace C201\Ddd\Tests\Transactions\Infrastructure\Application\Doctrine;

use C201\Ddd\Events\Application\EventManager;
use C201\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineEventStore;
use C201\Ddd\Transactions\Infrastructure\Application\Doctrine\DoctrineTransactionManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class DoctrineTransactionManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ObjectProphecy|EventManager
     */
    private $eventManager;

    /**
     * @var DoctrineTransactionManager
     */
    private $fixture;

    /**
     * @var ObjectProphecy|DoctrineEventStore
     */
    private $eventStore;

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
