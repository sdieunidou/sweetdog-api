<?php

declare(strict_types=1);

namespace Application\Contact\CreateContact;

use Domain\Contact\Contact;
use Domain\Contact\ContactRepositoryInterface;

final readonly class CreateContactUseCase
{
    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
    ) {}

    public function __invoke(CreateContactCommand $command): CreateContactResponse
    {
        $contact = Contact::create($command->subject, $command->message);

        $id = $this->contactRepository->create($contact);

        $contactWithId = $contact->withId($id);

        return new CreateContactResponse(
            id: $contactWithId->id,
            subject: $contactWithId->subject,
            message: $contactWithId->message,
        );
    }
}
