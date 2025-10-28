<?php

declare(strict_types=1);

namespace Domain\Auth;

final readonly class AuthTokens
{
    public function __construct(
        public string $token,
        public string $refreshToken,
        public int $tokenExpirationInstant,
    ) {
    }
}
