<?php

declare(strict_types=1);

namespace Application\Contact\CreateContact;

final readonly class CreateContactCommand
{
    public function __construct(
        public string $subject,
        public string $message,
    ) {
    }
}
