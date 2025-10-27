<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Domain\Auth\UserRepositoryInterface;
use Infrastructure\Auth\Doctrine\Entity\User as UserDoctrine;
use Infrastructure\Shared\Doctrine\Repository\AbstractBaseRepositoryDoctrine;

/**
 * @extends AbstractBaseRepositoryDoctrine<UserDoctrine>
 */
final class UserRepositoryDoctrine extends AbstractBaseRepositoryDoctrine implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(UserDoctrine::class);
    }

    public function findByIdentity(string $identity): ?UserDoctrine
    {
        return $this->repository->findOneBy(['authUserId' => $identity]);
    }
}
