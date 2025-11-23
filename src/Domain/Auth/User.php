<?php

declare(strict_types=1);

namespace Domain\Auth;

readonly class User
{
    public function __construct(
        public array $roles = [],
        public ?string $id = null,
    ) {}

    public static function create(array $roles = [], ?string $id = null): self
    {
        return new self(
            roles: $roles,
            id: $id,
        );
    }

    public function withId(?string $id): self
    {
        return self::create(
            roles: $this->roles,
            id: $id,
        );
    }

    public function withRoles(array $roles): self
    {
        return self::create(
            roles: $roles,
            id: $this->id
        );
    }
}
