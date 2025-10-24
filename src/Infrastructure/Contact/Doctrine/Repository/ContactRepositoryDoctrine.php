<?php

declare(strict_types=1);

namespace Infrastructure\Contact\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Domain\Contact\Contact;
use Domain\Contact\ContactRepositoryInterface;
use Infrastructure\Contact\Doctrine\Entity\Contact as ContactDoctrine;
use Infrastructure\Shared\Doctrine\Repository\AbstractBaseRepositoryDoctrine;

/**
 * @extends AbstractBaseRepositoryDoctrine<ContactDoctrine>
 */
final class ContactRepositoryDoctrine extends AbstractBaseRepositoryDoctrine implements ContactRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(ContactDoctrine::class);
    }

    public function create(Contact $contact): void
    {
        $this->entityManager->persist($contact);
        $this->entityManager->flush();
    }
}
