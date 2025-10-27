<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\Column(type: Types::STRING)]
    private string $authUserId;

    private array $roles = [];

    public function __construct(
        string $authUserId,
        array $roles = ['ROLE_USER'],
        ?int $id = null,
    ) {
        $this->authUserId = $authUserId;
        $this->roles = array_merge($roles, ['ROLE_USER']);
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthUserId(): string
    {
        return $this->authUserId;
    }

    public function getRoles(): array
    {
        return array_merge($this->roles, ['ROLE_USER']);
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->getId();
    }

    public static function create(string $authUserId, array $roles = ['ROLE_USER'], ?int $id = null): self
    {
        return new self($authUserId, $roles, $id);
    }
}
