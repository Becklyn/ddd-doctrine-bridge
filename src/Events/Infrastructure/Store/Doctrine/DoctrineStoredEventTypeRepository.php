<?php

namespace C201\Ddd\Events\Infrastructure\Store\Doctrine;

use C201\Ddd\Events\Domain\DomainEvent;
use Ramsey\Uuid\Uuid;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Tightenco\Collect\Support\Collection;

/**
 * @author Marko Vujnovic <mv@201created.de>
 * @since  2019-08-21
 */
class DoctrineStoredEventTypeRepository
{
    private EntityManagerInterface $em;

    private ObjectRepository $repository;

    /**
     * @var DoctrineStoredEventType[]|Collection
     */
    private $freshlyCreated;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(DoctrineStoredEventType::class);
        $this->freshlyCreated = Collection::make();
    }

    public function findOneOrCreate(DomainEvent $event): DoctrineStoredEventType
    {
        $freshlyCreatedMatch = $this->freshlyCreated->filter(fn(DoctrineStoredEventType $element) => $element->name() === get_class($event));
        if ($freshlyCreatedMatch->count() > 1) {
            $className = get_class($event);
            throw new \Exception("Found more than one event type with name '$className'");
        }

        if ($freshlyCreatedMatch->count() === 1) {
            return $freshlyCreatedMatch->first();
        }

        $eventType = $this->repository->findOneBy(['name' => get_class($event)]);
        if ($eventType === null) {
            $eventType = new DoctrineStoredEventType(Uuid::uuid4(), get_class($event));
            $this->em->persist($eventType);
            $this->freshlyCreated->push($eventType);
        }

        return $eventType;
    }

    public function clearFreshlyCreated(): void
    {
        $this->freshlyCreated = Collection::make();
    }
}
