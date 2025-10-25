<?php

declare(strict_types=1);

namespace Tests\Contact\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Tests\Traits\CommonAssertions;

class ContactEndpoints
{
    use CommonAssertions;

    private AbstractBrowser $client;
    private WebTestCase $testCase;
    private array $lastResponseData = [];

    public function __construct(AbstractBrowser $client, WebTestCase $testCase)
    {
        $this->client = $client;
        $this->testCase = $testCase;
    }

    public function createContact(array $data): self
    {
        $this->client->jsonRequest('POST', '/api/contacts', $data);
        $this->setLastResponseData(json_decode($this->getResponse()->getContent(), true));

        return $this;
    }

    public function createContactWithInvalidJson(): self
    {
        $this->client->request('POST', '/api/contacts', content: 'invalid json');
        $this->setLastResponseData(json_decode($this->getResponse()->getContent(), true));

        return $this;
    }
}
