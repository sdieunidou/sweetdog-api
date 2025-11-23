<?php

declare(strict_types=1);

namespace Domain\Contact;

readonly class Contact
{
    public function __construct(
        public string $subject,
        public string $message,
        public ?int $id,
    ) {}

    public static function create(string $subject, string $message, ?int $id = null): self
    {
        return new self(
            subject: $subject,
            message: $message,
            id: $id,
        );
    }

    public function withId(?int $id): self
    {
        return self::create(
            subject: $this->subject,
            message: $this->message,
            id: $id,
        );
    }
}
