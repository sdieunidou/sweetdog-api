<?php

declare(strict_types=1);

namespace Application\Contact\CreateContact;

use Domain\Contact\Contact;
use Domain\Contact\ContactRepositoryInterface;

final readonly class CreateContactUseCase
{
    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
    ) {
    }

    public function __invoke(CreateContactRequest $request): CreateContactResponse
    {
        $contact = Contact::create($request->subject, $request->message);

        $id = $this->contactRepository->create($contact);

        return new CreateContactResponse(
            $id,
            $request->subject,
            $request->message
        );
    }
}
