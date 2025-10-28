<?php

declare(strict_types=1);

namespace Tests\Auth\Functional\Login;

class LoginRequestBuilder
{
    private array $data = [];

    public function withValidData(): self
    {
        return $this->withEmail('admin@admin.com')
                    ->withPassword('password');
    }

    public function withEmail(string $email): self
    {
        $this->data['email'] = $email;

        return $this;
    }

    public function withPassword(string $password): self
    {
        $this->data['password'] = $password;

        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }
}
