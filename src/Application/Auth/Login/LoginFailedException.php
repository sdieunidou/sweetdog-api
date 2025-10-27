<?php

declare(strict_types=1);

namespace Application\Auth\Login;

final class LoginFailedException extends \Exception
{
    public function __construct(string $message = 'Failed to login', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
