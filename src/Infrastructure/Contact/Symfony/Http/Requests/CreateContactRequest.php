<?php

declare(strict_types=1);

namespace Infrastructure\Contact\Symfony\Http\Requests;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateContactRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le sujet ne peut pas être vide')]
        public string $subject = '',

        #[Assert\NotBlank(message: 'Le message ne peut pas être vide')]
        public string $message = '',
    ) {
    }
}
