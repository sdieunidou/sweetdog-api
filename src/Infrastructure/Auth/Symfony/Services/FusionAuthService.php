<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Services;

use Domain\Auth\AuthenticationResponse;
use Domain\Auth\AuthenticationServiceInterface;
use Domain\Auth\JwtClaims;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FusionAuthService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(FUSIONAUTH_URL)%')]
        private readonly string $fusionAuthUrl,
        #[Autowire('%env(FUSIONAUTH_APPLICATION_ID)%')]
        private readonly string $fusionAuthApplicationId,
        #[Autowire('%env(FUSIONAUTH_API_KEY)%')]
        private readonly string $fusionAuthApiKey,
    ) {
    }

    public function decodeAndValidateJwtToken(string $token): JwtClaims
    {
        $response = $this->httpClient->request('GET', sprintf('%s/api/jwt/validate', $this->fusionAuthUrl), [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $token),
            ],
            'json' => [
                'access_token' => $token,
            ],
        ]);

        $data = $response->toArray();

        if (!isset($data['jwt']) || !is_array($data['jwt'])) {
            throw new \RuntimeException('Invalid JWT validation');
        }

        $jwt = $data['jwt'];

        if (!isset($jwt['applicationId']) || $jwt['applicationId'] !== $this->fusionAuthApplicationId) {
            throw new \RuntimeException('Invalid application ID');
        }

        $this->verifyUserIsActive($jwt['sub']);
        $this->verifySessionNotExpired($jwt['exp'], $token);

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
            roles: $jwt['roles'] ?? [],
            sid: $jwt['sid'],
            tid: $jwt['tid'],
            tty: $jwt['tty'],
        );
    }

    public function fetchUserInfo(string $token): JwtClaims
    {
        $response = $this->httpClient->request('GET', sprintf('%s/api/user', $this->fusionAuthUrl), [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $token),
            ],
        ]);

        $data = $response->toArray();

        if (!isset($data['user'])) {
            throw new \RuntimeException('User information not found');
        }

        // Récupérer la registration pour cette application
        if (!isset($data['user']['registrations']) || !is_array($data['user']['registrations'])) {
            throw new \RuntimeException('User registrations not found');
        }

        $registration = current(array_filter($data['user']['registrations'], function ($registration) {
            return $registration['applicationId'] === $this->fusionAuthApplicationId;
        }));

        if (!$registration) {
            throw new \RuntimeException('Registration not found for this application');
        }

        // Cette méthode retourne le JWT décodé, donc on simule une structure similaire
        // mais avec les données actuelles de l'utilisateur
        return new JwtClaims(
            aud: $this->fusionAuthApplicationId,
            exp: 0, // Pas disponible via cet endpoint
            iat: 0, // Pas disponible via cet endpoint
            iss: 'acme.com', // Valeur par défaut
            jti: '', // Pas disponible
            sub: $data['user']['id'],
            applicationId: $this->fusionAuthApplicationId,
            authTime: 0, // Pas disponible
            authenticationType: '', // Pas disponible
            roles: $registration['roles'] ?? [],
            sid: '', // Pas disponible
            tid: '', // Pas disponible
            tty: '', // Pas disponible
        );
    }

    public function authenticateUser(string $email, string $password, string $ipAddress): AuthenticationResponse
    {
        $response = $this->httpClient->request('POST', sprintf('%s/api/login', $this->fusionAuthUrl), [
            'headers' => [
                'Authorization' => $this->fusionAuthApiKey,
            ],
            'json' => [
                'applicationId' => $this->fusionAuthApplicationId,
                'loginId' => $email,
                'password' => $password,
                'loginIdTypes' => ['email'],
                'ipAddress' => $ipAddress,
            ],
        ]);

        $data = $response->toArray();

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

        return new AuthenticationResponse(
            token: $data['token'],
            refreshToken: $data['refreshToken'],
            tokenExpirationInstant: (int) $data['tokenExpirationInstant'],
            active: $data['user']['active'],
            email: $data['user']['email'],
            preferredLanguages: $data['user']['preferredLanguages'],
            roles: $registration['roles'],
            birthDate: $data['user']['birthDate'] ?? null,
            lastName: $data['user']['lastName'] ?? null,
            firstName: $data['user']['firstName'] ?? null,
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
        $response = $this->httpClient->request('GET', sprintf('%s/api/user/%s', $this->fusionAuthUrl, $userId), [
            'headers' => [
                'Authorization' => $this->fusionAuthApiKey,
            ],
        ]);

        $data = $response->toArray();

        if (!isset($data['user']['active']) || true !== $data['user']['active']) {
            throw new \RuntimeException('User account has been deactivated');
        }
    }

    private function verifySessionNotExpired(int $expirationTimestamp, string $token): void
    {
        // 1. Vérifier que le token n'est pas expiré temporellement
        $currentTimestamp = time();

        if ($currentTimestamp >= $expirationTimestamp) {
            throw new \RuntimeException('Session has expired');
        }
    }
}
