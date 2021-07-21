<?php

namespace Becklyn\Ddd\Transactions\Infrastructure\Application\Doctrine;

use Becklyn\Ddd\Transactions\Application\TransactionManager;
use Becklyn\Ddd\Events\Application\EventManager;
use Becklyn\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineEventStore;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
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
