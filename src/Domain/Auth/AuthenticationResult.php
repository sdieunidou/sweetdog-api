<?php

declare(strict_types=1);

namespace Domain\Auth;

final readonly class AuthenticationResult
{
    public function __construct(
        public User $user,
        public AuthTokens $tokens,
    ) {
    }
}
