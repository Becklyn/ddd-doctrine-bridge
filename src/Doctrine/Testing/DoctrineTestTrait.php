<?php

namespace Becklyn\Ddd\Doctrine\Testing;

use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 * @since  2019-08-23
 */
trait DoctrineTestTrait
{
    /** @var ObjectProphecy|EntityManagerInterface */
    protected ObjectProphecy $em;
    /** @var ObjectProphecy|ObjectRepository */
    protected ObjectProphecy $repository;

    protected function initDoctrineTestTrait(string $classForRepository = null): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->repository = $this->prophesize(ObjectRepository::class);
        if ($classForRepository) {
            $this->em->getRepository($classForRepository)->willReturn($this->repository->reveal());
        }
    }
}
