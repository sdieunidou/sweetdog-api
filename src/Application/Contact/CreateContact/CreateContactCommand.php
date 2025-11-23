<?php

declare(strict_types=1);

namespace Application\Contact\CreateContact;

final readonly class CreateContactCommand
{
    public function __construct(
        public string $subject,
        public string $message,
    ) {}

    public static function create(string $subject, string $message): self
    {
        return new self(subject: $subject, message: $message);
    }
}
