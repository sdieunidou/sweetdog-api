<?php

declare(strict_types=1);

namespace Domain\Contact;

interface ContactRepositoryInterface
{
    public function create(Contact $contact): int;
}
