<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Controller;

use Application\Auth\Login\LoginCommand;
use Application\Auth\Login\LoginFailedException;
use Application\Auth\Login\LoginUseCase;
use Infrastructure\Auth\Symfony\Http\Requests\LoginRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth/login', name: 'api_auth_login', format: 'json', methods: [Request::METHOD_POST])]
final class LoginController extends AbstractController
{
    public function __construct(
        private readonly LoginUseCase $loginUseCase,
    ) {}

    public function __invoke(#[MapRequestPayload] LoginRequest $loginRequest, Request $request): JsonResponse
    {
        $command = new LoginCommand(
            email: $loginRequest->email,
            password: $loginRequest->password,
            ipAddress: $request->getClientIp()
        );

        try {
            $token = ($this->loginUseCase)($command);
        } catch (LoginFailedException $e) {
            return new JsonResponse(
                [
                    'error' => 'Authentication failed',
                    'message' => 'Invalid credentials',
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return new JsonResponse($token);
    }
}