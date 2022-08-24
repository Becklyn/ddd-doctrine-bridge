<?php declare(strict_types=1);

namespace Becklyn\Ddd\Doctrine\Testing;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 *
 * @since  2019-08-23
 */
trait DoctrineTestTrait
{
    protected ObjectProphecy|EntityManagerInterface $em;
    protected ObjectProphecy|ObjectRepository $repository;

    protected function initDoctrineTestTrait(?string $classForRepository = null) : void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->repository = $this->prophesize(ObjectRepository::class);

        if ($classForRepository) {
            $this->em->getRepository($classForRepository)->willReturn($this->repository->reveal());
        }
    }
}
