<?php

declare(strict_types=1);

namespace Domain\Auth;

interface AuthenticationServiceInterface
{
    public function authenticateUser(string $email, string $password, string $ipAddress): AuthenticationResult;

    public function validateJwtAndGetClaims(string $token): JwtClaims;
}
