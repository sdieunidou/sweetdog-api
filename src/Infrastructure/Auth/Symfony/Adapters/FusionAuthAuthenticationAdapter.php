<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Adapters;

use Domain\Auth\AuthenticationResult;
use Domain\Auth\AuthenticationServiceInterface;
use Domain\Auth\AuthTokens;
use Domain\Auth\JwtClaims;
use Domain\Auth\User;
use Infrastructure\Auth\Symfony\Client\FusionAuthClient;
use Infrastructure\Auth\Symfony\Validators\JwtClaimsResponseValidator;
use Infrastructure\Auth\Symfony\Validators\LoginResponseValidator;
use Infrastructure\Auth\Symfony\Validators\UserResponseValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class FusionAuthAuthenticationAdapter implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly FusionAuthClient $fusionAuthClient,
        private readonly JwtClaimsResponseValidator $jwtClaimsValidator,
        private readonly LoginResponseValidator $loginResponseValidator,
        private readonly UserResponseValidator $userResponseValidator,
        #[Autowire('%env(FUSIONAUTH_APPLICATION_ID)%')]
        private readonly string $fusionAuthApplicationId,
    ) {
    }

    public function validateJwtAndGetClaims(string $token): JwtClaims
    {
        $data = $this->fusionAuthClient->validateJwtToken($token);
        $claims = $this->jwtClaimsValidator->validateAndBuildClaims($data, $this->fusionAuthApplicationId);

        $this->verifyUserIsActive($claims->sub);

        return $claims;
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

        $this->loginResponseValidator->validate($data);

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

    private function verifyUserIsActive(string $userId): void
    {
        $data = $this->fusionAuthClient->getUser($userId);

        $this->userResponseValidator->validate($data);

        if (true !== $data['user']['active']) {
            throw new \RuntimeException('User account has been deactivated');
        }
    }
}
