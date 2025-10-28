<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Client;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FusionAuthClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(FUSIONAUTH_URL)%')]
        private readonly string $fusionAuthUrl,
        #[Autowire('%env(FUSIONAUTH_API_KEY)%')]
        private readonly string $fusionAuthApiKey,
    ) {
    }

    public function validateJwtToken(string $token): array
    {
        $response = $this->httpClient->request('GET', sprintf('%s/api/jwt/validate', $this->fusionAuthUrl), [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $token),
            ],
            'json' => [
                'access_token' => $token,
            ],
        ]);

        return $response->toArray();
    }

    public function login(array $payload): array
    {
        $response = $this->httpClient->request('POST', sprintf('%s/api/login', $this->fusionAuthUrl), [
            'headers' => [
                'Authorization' => $this->fusionAuthApiKey,
            ],
            'json' => $payload,
        ]);

        return $response->toArray();
    }

    public function getUser(string $userId): array
    {
        $response = $this->httpClient->request('GET', sprintf('%s/api/user/%s', $this->fusionAuthUrl, $userId), [
            'headers' => [
                'Authorization' => $this->fusionAuthApiKey,
            ],
        ]);

        return $response->toArray();
    }
}
