<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Validators;

use Domain\Auth\JwtClaims;

final class JwtClaimsResponseValidator
{
    public function validateAndBuildClaims(array $data, string $expectedApplicationId): JwtClaims
    {
        if (!isset($data['jwt']) || !is_array($data['jwt'])) {
            throw new \RuntimeException('Invalid JWT validation');
        }

        $jwt = $data['jwt'];

        if (!isset($jwt['applicationId']) || $jwt['applicationId'] !== $expectedApplicationId) {
            throw new \RuntimeException('Invalid application ID');
        }

        if (!isset($jwt['exp']) || time() > (int) $jwt['exp']) {
            throw new \RuntimeException('Session has expired');
        }

        return new JwtClaims(
            aud: $jwt['aud'],
            exp: (int) $jwt['exp'],
            iat: (int) $jwt['iat'],
            iss: $jwt['iss'],
            jti: $jwt['jti'],
            sub: $jwt['sub'],
            applicationId: $jwt['applicationId'],
            authTime: (int) $jwt['auth_time'],
            authenticationType: $jwt['authenticationType'],
            sid: $jwt['sid'],
            tid: $jwt['tid'],
            tty: $jwt['tty'],
        );
    }
}
