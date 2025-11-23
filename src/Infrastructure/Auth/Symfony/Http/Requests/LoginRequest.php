<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Http\Requests;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'email ne peut pas être vide')]
        #[Assert\Email(message: 'L\'email n\'est pas valide')]
        public string $email = '',
        #[Assert\NotBlank(message: 'Le mot de passe ne peut pas être vide')]
        #[Assert\Length(min: 6, max: 20, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères', maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères')]
        public string $password = '',
    ) {}
}
