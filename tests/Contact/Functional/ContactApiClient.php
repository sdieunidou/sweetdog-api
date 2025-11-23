<?php

declare(strict_types=1);

namespace Tests\Contact\Functional;

use Tests\Shared\Functional\ApiClient;

class ContactApiClient extends ApiClient
{
    public function createContact(array $data): self
    {
        return $this
            ->withAuthentication()
            ->post('/api/contacts', $data)
        ;
    }
}
