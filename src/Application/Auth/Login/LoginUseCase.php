<?php

declare(strict_types=1);

namespace Application\Auth\Login;

use Domain\Auth\AuthenticationServiceInterface;
use Psr\Log\LoggerInterface;

final readonly class LoginUseCase
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authenticationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(LoginCommand $command): LoginResponse
    {
        try {
            $authenticationResult = $this->authenticationService->authenticateUser($command->email, $command->password, $command->ipAddress);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new LoginFailedException(previous: $e);
        }

        return LoginResponse::create(
            token: $authenticationResult->tokens->token,
            refreshToken: $authenticationResult->tokens->refreshToken,
            tokenExpirationInstant: $authenticationResult->tokens->tokenExpirationInstant,
        );
    }
}
