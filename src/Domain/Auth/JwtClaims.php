<?php

declare(strict_types=1);

namespace Domain\Auth;

final readonly class JwtClaims
{
    public function __construct(
        public string $aud,
        public int $exp,
        public int $iat,
        public string $iss,
        public string $jti,
        public string $sub,
        public string $applicationId,
        public int $authTime,
        public string $authenticationType,
        public array $roles,
        public string $sid,
        public string $tid,
        public string $tty,
    ) {
    }
}
