<?php

declare(strict_types=1);

namespace Infrastructure\Contact\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'contacts')]
final class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $subject;

    #[ORM\Column(type: Types::TEXT)]
    private string $message;

    public function __construct(
        string $subject,
        string $message,
        ?int $id = null,
    ) {
        $this->subject = $subject;
        $this->message = $message;
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
