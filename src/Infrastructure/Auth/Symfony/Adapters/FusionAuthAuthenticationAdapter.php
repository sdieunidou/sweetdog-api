<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Adapters;

use Domain\Auth\AuthenticationResult;
use Domain\Auth\AuthenticationServiceInterface;
use Domain\Auth\AuthTokens;
use Domain\Auth\JwtClaims;
use Domain\Auth\User;
use Infrastructure\Auth\Symfony\Client\FusionAuthClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FusionAuthAuthenticationAdapter implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly FusionAuthClient $fusionAuthClient,
        #[Autowire('%env(FUSIONAUTH_APPLICATION_ID)%')]
        private readonly string $fusionAuthApplicationId,
    ) {
    }

    public function validateJwtAndGetClaims(string $token): JwtClaims
    {
        $data = $this->fusionAuthClient->validateJwtToken($token);

        if (!isset($data['jwt']) || !is_array($data['jwt'])) {
            throw new \RuntimeException('Invalid JWT validation');
        }

        $jwt = $data['jwt'];

        if (!isset($jwt['applicationId']) || $jwt['applicationId'] !== $this->fusionAuthApplicationId) {
            throw new \RuntimeException('Invalid application ID');
        }

        if (time() > $jwt['exp']) {
            throw new \RuntimeException('Session has expired');
        }

        $this->verifyUserIsActive($jwt['sub']);

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

    public function authenticateUser(string $email, string $password, string $ipAddress): AuthenticationResult
    {
        $data = $this->fusionAuthClient->login([
            'applicationId' => $this->fusionAuthApplicationId,
            'loginId' => $email,
            'password' => $password,
            'loginIdTypes' => ['email'],
            'ipAddress' => $ipAddress,
        ]);

        $this->validateLoginResponseData($data);

        if (!isset($data['user']['registrations']) || !is_array($data['user']['registrations'])) {
            throw new \RuntimeException('User registrations not found in FusionAuth response');
        }

        $registration = current(array_filter($data['user']['registrations'], function ($registration) {
            return $registration['applicationId'] === $this->fusionAuthApplicationId;
        }));

        if (!$registration) {
            throw new \RuntimeException('Registration not found for this application');
        }

        if (!isset($registration['roles'])) {
            throw new \RuntimeException('Roles not found in registration');
        }

        if (true !== $data['user']['active']) {
            throw new \RuntimeException('User account is not active');
        }

        $user = User::create(
            roles: $registration['roles'],
            id: $data['user']['id'],
        );

        $tokens = new AuthTokens(
            token: $data['token'],
            refreshToken: $data['refreshToken'],
            tokenExpirationInstant: (int) $data['tokenExpirationInstant'],
        );

        return new AuthenticationResult(
            user: $user,
            tokens: $tokens,
        );
    }

    private function validateLoginResponseData(array $data): void
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(true);

        $resolver->setRequired([
            'token',
            'refreshToken',
            'tokenExpirationInstant',
        ]);

        $resolver->resolve($data);

        $this->validateUserResponseData($data);
    }

    private function validateUserResponseData(array $data): void
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(true);

        $resolver->setRequired([
            'user',
        ]);

        $resolver->setAllowedTypes('user', 'array');
        $resolver->setNormalizer('user', function ($options, $value) {
            $userResolver = new OptionsResolver();
            $userResolver->setIgnoreUndefined(true);

            $userResolver->setRequired([
                'active',
                'email',
                'preferredLanguages',
            ]);

            $userResolver->setDefined([
                'birthDate',
                'lastName',
                'firstName',
            ]);

            $userResolver->setDefaults([
                'birthDate' => null,
                'lastName' => null,
                'firstName' => null,
            ]);

            return $userResolver->resolve($value);
        });

        $resolver->resolve($data);
    }

    private function verifyUserIsActive(string $userId): void
    {
        $data = $this->fusionAuthClient->getUser($userId);

        $this->validateUserResponseData($data);

        if (true !== $data['user']['active']) {
            throw new \RuntimeException('User account has been deactivated');
        }
    }
}
