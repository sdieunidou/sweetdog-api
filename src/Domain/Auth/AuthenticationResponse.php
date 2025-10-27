<?php

declare(strict_types=1);

namespace Domain\Auth;

final readonly class AuthenticationResponse
{
    public function __construct(
        public string $token,
        public string $refreshToken,
        public int $tokenExpirationInstant,
        public bool $active,
        public string $email,
        public array $preferredLanguages,
        public array $roles,
        public ?string $lastName,
        public ?string $firstName,
        public ?string $birthDate,
    ) {
    }
}
