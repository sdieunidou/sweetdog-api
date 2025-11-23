<?php

declare(strict_types=1);

namespace Application\Auth\Login;

final readonly class LoginResponse
{
    public function __construct(
        public string $token,
        public string $refreshToken,
        public int $tokenExpirationInstant,
    ) {}

    public static function create(string $token, string $refreshToken, int $tokenExpirationInstant): self
    {
        return new self(
            token: $token,
            refreshToken: $refreshToken,
            tokenExpirationInstant: $tokenExpirationInstant,
        );
    }
}
