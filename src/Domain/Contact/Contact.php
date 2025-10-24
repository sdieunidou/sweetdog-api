<?php

declare(strict_types=1);

namespace Domain\Contact;

readonly class Contact
{
    public function __construct(
        protected string $subject,
        protected string $message,
        protected ?int $id,
    ) {
    }

    public static function create(string $subject, string $message, ?int $id = null): self
    {
        return new self(
            subject: $subject,
            message: $message,
            id: $id,
        );
    }
}
