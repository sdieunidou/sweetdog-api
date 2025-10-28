<?php

declare(strict_types=1);

namespace Tests\Auth\Functional\Login;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Auth\Functional\AuthApiClient;
use Tests\Shared\Functional\CommonAssertions;

class LoginApiTest extends WebTestCase
{
    use CommonAssertions;

    private function createApiClient(): AuthApiClient
    {
        return new AuthApiClient(static::createClient(), $this);
    }

    public function testLoginSuccess(): void
    {
        $client = $this->createApiClient()
            ->login((new LoginRequestBuilder())->withValidData()->build())
        ;

        $responseData = $client->getResponseData();

        $this
            ->assertHttpSuccess()
            ->assertLoginSuccess($responseData)
        ;
    }

    protected function assertLoginSuccess(array $responseData): self
    {
        $this->assertArrayHasKey('token', $responseData);
        $this->assertNotEmpty($responseData['token']);
        $this->assertIsString($responseData['token']);

        $this->assertArrayHasKey('refreshToken', $responseData);
        $this->assertNotEmpty($responseData['refreshToken']);
        $this->assertIsString($responseData['refreshToken']);

        $this->assertArrayHasKey('tokenExpirationInstant', $responseData);
        $this->assertIsInt($responseData['tokenExpirationInstant']);
        $this->assertGreaterThan(0, $responseData['tokenExpirationInstant']);

        return $this;
    }
}
