<?php

declare(strict_types=1);

namespace Tests\Shared\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiClient
{
    protected AbstractBrowser $client;
    protected WebTestCase $testCase;

    private array $lastResponseData = [];
    private bool $withAuthentication = false;

    public function __construct(AbstractBrowser $client, WebTestCase $testCase)
    {
        $this->client = $client;
        $this->testCase = $testCase;
    }

    public function withAuthentication(): self
    {
        $this->withAuthentication = true;

        return $this;
    }

    public function getResponseData(): array
    {
        return $this->lastResponseData;
    }

    protected function jsonRequest(string $method, string $uri, mixed $data = [], array $server = []): self
    {
        if ($this->withAuthentication) {
            $server['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $this->fetchJwtToken('admin@admin.com', 'password'));
        }

        $this->client->jsonRequest($method, $uri, $data, $server);
        $this->lastResponseData = json_decode($this->getResponse()->getContent(), true);

        return $this;
    }

    protected function post(string $uri, mixed $data): self
    {
        return $this->jsonRequest('POST', $uri, $data);
    }

    protected function get(string $uri): self
    {
        return $this->jsonRequest('GET', $uri);
    }

    protected function getResponse(): Response
    {
        return $this->client->getResponse();
    }

    protected function fetchJwtToken(string $email, string $password): string
    {
        $this->client->request('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $data = json_decode($this->getResponse()->getContent(), true);

        if (empty($data['token'])) {
            throw new \RuntimeException('Failed to fetch JWT token');
        }

        return $data['token'];
    }
}
