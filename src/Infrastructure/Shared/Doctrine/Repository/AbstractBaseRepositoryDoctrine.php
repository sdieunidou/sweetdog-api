<?php

declare(strict_types=1);

namespace Infrastructure\Shared\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @template T of object
 */
abstract class AbstractBaseRepositoryDoctrine
{
    /**
     * @var EntityRepository<T>
     */
    protected EntityRepository $repository;
}
