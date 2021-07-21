<?php

namespace Becklyn\Ddd\Events\Infrastructure\Store\Doctrine;

use Becklyn\Ddd\Events\Domain\DomainEvent;
use Becklyn\Ddd\Identity\Domain\AggregateId;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Collection;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 * @since  2019-08-21
 */
class DoctrineStoredEventAggregateRepository
{
    private EntityManagerInterface $em;
    private ObjectRepository $repository;
    private DoctrineStoredEventAggregateTypeRepository $aggregateTypeRepository;

    /** @var DoctrineStoredEventAggregate[]|Collection */
    private Collection $freshlyCreated;

    public function __construct(EntityManagerInterface $em, DoctrineStoredEventAggregateTypeRepository $aggregateTypeRepository)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(DoctrineStoredEventAggregate::class);
        $this->aggregateTypeRepository = $aggregateTypeRepository;
        $this->freshlyCreated = Collection::make();
    }

    public function findOneOrCreate(DomainEvent $event): DoctrineStoredEventAggregate
    {
        $aggregateType = $this->aggregateTypeRepository->findOneOrCreate($event);

        $freshlyCreatedMatch = $this->findFreshlyCreated($event->aggregateId(), $aggregateType);
        if ($freshlyCreatedMatch) {
            return $freshlyCreatedMatch;
        }

        /** @var DoctrineStoredEventAggregate $aggregate */
        $aggregate = $this->repository->findOneBy(['id' => $event->aggregateId()->asString(), 'aggregateType' => $aggregateType->id()]);
        if ($aggregate === null) {
            $aggregate = new DoctrineStoredEventAggregate($event->aggregateId()->asString(), $aggregateType, 0);
            $this->em->persist($aggregate);
            $this->freshlyCreated->push($aggregate);
        }

        return $aggregate;
    }

    private function findFreshlyCreated(AggregateId $aggregateId, DoctrineStoredEventAggregateType $aggregateType): ?DoctrineStoredEventAggregate
    {
        $freshlyCreatedMatch = $this->freshlyCreated
            ->filter(static function (DoctrineStoredEventAggregate $element) use ($aggregateId, $aggregateType) {
                return $element->id() === $aggregateId->asString() && $element->aggregateType()->id() === $aggregateType->id();
            });
        if ($freshlyCreatedMatch->count() > 1) {
            throw new \Exception("Found more than one aggregate with id '{$aggregateId->asString()}}' for type '{$aggregateType->name()}'");
        }

        if ($freshlyCreatedMatch->count() === 1) {
            return $freshlyCreatedMatch->first();
        }

        return null;
    }

    public function clearFreshlyCreated(): void
    {
        $this->freshlyCreated = Collection::make();
        $this->aggregateTypeRepository->clearFreshlyCreated();
    }
}
