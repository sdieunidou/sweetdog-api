<?php

declare(strict_types=1);

namespace Application\Contact;

use Domain\Contact\Contact;
use Domain\Contact\ContactRepositoryInterface;

final readonly class CreateContactUseCase
{
    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
    ) {
    }

    public function __invoke(string $subject, string $message): void
    {
        $contact = Contact::create($subject, $message);

        $this->contactRepository->create($contact);
    }
}
