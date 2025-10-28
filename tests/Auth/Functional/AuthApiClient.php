<?php

declare(strict_types=1);

namespace Tests\Auth\Functional;

use Tests\Shared\Functional\ApiClient;

class AuthApiClient extends ApiClient
{
    public function login(array $data): self
    {
        return $this->post('/api/auth/login', $data);
    }
}
