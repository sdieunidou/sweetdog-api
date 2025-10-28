<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            [
                'error' => 'Authentication failed',
                'message' => 'Authentication required. Please provide a valid Bearer token.',
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                'WWW-Authenticate' => 'Bearer',
            ]
        );
    }
}
