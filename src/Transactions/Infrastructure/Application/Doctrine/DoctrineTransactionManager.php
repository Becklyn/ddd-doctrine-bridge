<?php

namespace C201\Ddd\Transactions\Infrastructure\Application\Doctrine;

use C201\Ddd\Transactions\Application\TransactionManager;
use C201\Ddd\Events\Application\EventManager;
use C201\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineEventStore;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Marko Vujnovic <mv@201created.de>
 * @since  2019-06-07
 */
class DoctrineTransactionManager implements TransactionManager
{
    private EntityManagerInterface $entityManager;

    private EventManager $eventManager;

    private DoctrineEventStore $eventStore;

    public function __construct(EntityManagerInterface $entityManager, EventManager $eventManager, DoctrineEventStore  $eventStore)
    {
        $this->entityManager = $entityManager;
        $this->eventManager = $eventManager;
        $this->eventStore = $eventStore;
    }

    public function begin(): void
    {
        // nothing to actually do here
    }

    public function commit(): void
    {
        try {
            $this->entityManager->flush();
            $this->eventStore->clearFreshlyCreated();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        $this->entityManager->clear();
        $this->eventManager->flush();
    }

    public function rollback(): void
    {
        $this->eventStore->clearFreshlyCreated();
        $this->entityManager->clear();
        $this->eventManager->clear();
    }
}
