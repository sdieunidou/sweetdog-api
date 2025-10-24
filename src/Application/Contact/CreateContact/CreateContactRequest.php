<?php

declare(strict_types=1);

namespace Application\Contact\CreateContact;

final readonly class CreateContactRequest
{
    public function __construct(
        public string $subject,
        public string $message,
    ) {
    }
}
