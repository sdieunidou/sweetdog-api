<?php

declare(strict_types=1);

namespace Domain\Auth;

use Infrastructure\Auth\Doctrine\Entity\User;

interface UserRepositoryInterface
{
    public function findByIdentity(string $identity): ?User;
}
