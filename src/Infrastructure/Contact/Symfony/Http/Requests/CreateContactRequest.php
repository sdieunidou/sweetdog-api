<?php

declare(strict_types=1);

namespace Infrastructure\Contact\Symfony\Http\Requests;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateContactRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le sujet ne peut pas être vide')]
        #[Assert\Length(min: 3, max: 100, minMessage: 'Le sujet doit contenir au moins {{ limit }} caractères', maxMessage: 'Le sujet ne peut pas dépasser {{ limit }} caractères')]
        #[Assert\Regex(pattern: '/^[a-zA-Z0-9\s\-\.\,\!\?]+$/', message: 'Le sujet contient des caractères non autorisés')]
        public string $subject = '',

        #[Assert\NotBlank(message: 'Le message ne peut pas être vide')]
        #[Assert\Length(min: 10, max: 1000, minMessage: 'Le message doit contenir au moins {{ limit }} caractères', maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères')]
        #[Assert\Regex(pattern: '/^[a-zA-Z0-9\s\-\.\,\!\?\:\;\'\"\(\)]+$/', message: 'Le message contient des caractères non autorisés')]
        public string $message = '',
    ) {
    }
}
