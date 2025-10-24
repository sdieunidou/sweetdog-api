<?php

declare(strict_types=1);

namespace Infrastructure\Contact\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Domain\Contact\Contact as DomainContact;

#[ORM\Entity]
#[ORM\Table(name: 'contacts')]
final readonly class Contact extends DomainContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id;

    #[ORM\Column(type: Types::STRING, length: 100)]
    protected string $subject;

    #[ORM\Column(type: Types::TEXT)]
    protected string $message;

    public function __construct(
        string $subject,
        string $message,
        ?int $id = null,
    ) {
        parent::__construct($subject, $message, $id);
    }
}
