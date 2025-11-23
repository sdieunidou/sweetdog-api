<?php

declare(strict_types=1);

namespace Application\Contact\CreateContact;

use Symfony\Component\Serializer\Annotation\Groups;

#[Groups(['contact:read'])]
final readonly class CreateContactResponse
{
    public function __construct(
        #[Groups(['contact:read'])]
        public int $id,
        #[Groups(['contact:read'])]
        public string $subject,
        #[Groups(['contact:read'])]
        public string $message,
    ) {}
}
