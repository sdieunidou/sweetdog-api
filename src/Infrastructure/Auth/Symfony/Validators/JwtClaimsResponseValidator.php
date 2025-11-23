<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Validators;

use Domain\Auth\JwtClaims;
use Infrastructure\Shared\Validators\AbstractOptionsResolverValidator;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class JwtClaimsResponseValidator extends AbstractOptionsResolverValidator
{
    public function validateAndBuildClaims(array $data, string $expectedApplicationId, bool $ignoreUndefined = true): JwtClaims
    {
        $validated = parent::validate($data, $ignoreUndefined);
        $jwt = $validated['jwt'];

        if ($jwt['applicationId'] !== $expectedApplicationId) {
            throw new \InvalidArgumentException('Invalid application ID');
        }

        if (time() > (int) $jwt['exp']) {
            throw new \InvalidArgumentException('Session has expired');
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

    protected function configureResolver(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['jwt']);
        $resolver->setAllowedTypes('jwt', 'array');
        $resolver->setNormalizer('jwt', function ($options, $value) {
            $jwtResolver = new OptionsResolver();
            $jwtResolver->setIgnoreUndefined(true);

            $jwtResolver->setRequired([
                'aud',
                'exp',
                'iat',
                'iss',
                'jti',
                'sub',
                'applicationId',
                'auth_time',
                'authenticationType',
                'sid',
                'tid',
                'tty',
            ]);

            $jwtResolver->setAllowedTypes('aud', 'string');
            $jwtResolver->setAllowedTypes('exp', ['int', 'string']);
            $jwtResolver->setAllowedTypes('iat', ['int', 'string']);
            $jwtResolver->setAllowedTypes('iss', 'string');
            $jwtResolver->setAllowedTypes('jti', 'string');
            $jwtResolver->setAllowedTypes('sub', 'string');
            $jwtResolver->setAllowedTypes('applicationId', 'string');
            $jwtResolver->setAllowedTypes('auth_time', ['int', 'string']);
            $jwtResolver->setAllowedTypes('authenticationType', 'string');
            $jwtResolver->setAllowedTypes('sid', 'string');
            $jwtResolver->setAllowedTypes('tid', 'string');
            $jwtResolver->setAllowedTypes('tty', 'string');

            return $jwtResolver->resolve($value);
        });
    }
}
