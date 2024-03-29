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
class DoctrineStoredEventAggregateTypeRepository
{
    private EntityManagerInterface $em;
    private ObjectRepository $repository;

    /** @var DoctrineStoredEventAggregateType[]|Collection */
    private Collection $freshlyCreated;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(DoctrineStoredEventAggregateType::class);
        $this->freshlyCreated = Collection::make();
    }

    public function findOneOrCreate(DomainEvent $event) : DoctrineStoredEventAggregateType
    {
        try {
            return $this->findOne($event->aggregateType());
        } catch (DoctrineStoredEventAggregateTypeNotFoundException $e) {
            return $this->create($event);
        }
    }

    /**
     * @throws DoctrineStoredEventAggregateTypeNotFoundException
     */
    public function findOne(string $aggregateType) : DoctrineStoredEventAggregateType
    {
        $freshlyCreatedMatch = $this->freshlyCreated->filter(fn(DoctrineStoredEventAggregateType $element) => $element->name() === $aggregateType);

        if ($freshlyCreatedMatch->count() > 1) {
            throw new \Exception("Found more than one aggregate type with name '{$aggregateType}'");
        }

        if (1 === $freshlyCreatedMatch->count()) {
            return $freshlyCreatedMatch->first();
        }

        /** @var ?DoctrineStoredEventAggregateType $aggregateType */
        $aggregateType = $this->repository->findOneBy(['name' => $aggregateType]);

        if (null === $aggregateType) {
            throw new DoctrineStoredEventAggregateTypeNotFoundException();
        }

        return $aggregateType;
    }

    private function create(DomainEvent $event) : DoctrineStoredEventAggregateType
    {
        $aggregateType = new DoctrineStoredEventAggregateType(Uuid::uuid4()->toString(), $event->aggregateType());
        $this->em->persist($aggregateType);
        $this->freshlyCreated->push($aggregateType);
        return $aggregateType;
    }

    public function clearFreshlyCreated() : void
    {
        $this->freshlyCreated = Collection::make();
    }
}
