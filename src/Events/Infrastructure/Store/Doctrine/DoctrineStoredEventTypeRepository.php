<?php declare(strict_types=1);

namespace Becklyn\Ddd\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Domain\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 *
 * @since  2019-08-21
 */
class DoctrineStoredEventTypeRepository
{
    private EntityManagerInterface $em;
    private ObjectRepository $repository;

    /** @var DoctrineStoredEventType[]|Collection */
    private Collection $freshlyCreated;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(DoctrineStoredEventType::class);
        $this->freshlyCreated = Collection::make();
    }

    public function findOneOrCreate(DomainEvent $event) : DoctrineStoredEventType
    {
        $freshlyCreatedMatch = $this->freshlyCreated->filter(fn(DoctrineStoredEventType $element) => $element->name() === \get_class($event));

        if ($freshlyCreatedMatch->count() > 1) {
            $className = \get_class($event);
            throw new \Exception("Found more than one event type with name '{$className}'");
        }

        if (1 === $freshlyCreatedMatch->count()) {
            return $freshlyCreatedMatch->first();
        }

        /** @var ?DoctrineStoredEventType $eventType */
        $eventType = $this->repository->findOneBy(['name' => \get_class($event)]);

        if (null === $eventType) {
            $eventType = new DoctrineStoredEventType(Uuid::uuid4()->toString(), \get_class($event));
            $this->em->persist($eventType);
            $this->freshlyCreated->push($eventType);
        }

        return $eventType;
    }

    public function clearFreshlyCreated() : void
    {
        $this->freshlyCreated = Collection::make();
    }
}
