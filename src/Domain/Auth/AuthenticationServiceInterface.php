<?php

declare(strict_types=1);

namespace Domain\Auth;

interface AuthenticationServiceInterface
{
    public function authenticateUser(string $email, string $password, string $ipAddress): AuthenticationResponse;

    public function decodeAndValidateJwtToken(string $token): JwtClaims;

    public function fetchUserInfo(string $token): JwtClaims;
}
