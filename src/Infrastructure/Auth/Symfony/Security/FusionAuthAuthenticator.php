<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Security;

use Domain\Auth\UserRepositoryInterface;
use Infrastructure\Auth\Symfony\Adapters\FusionAuthAuthenticationAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class FusionAuthAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly FusionAuthAuthenticationAdapter $fusionAuthAuthenticationAdapter,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->extractToken($request);
        if (null === $token) {
            throw new CustomUserMessageAuthenticationException('No token provided');
        }

        try {
            $jwtClaims = $this->fusionAuthAuthenticationAdapter->validateJwtAndGetClaims($token);
        } catch (\Throwable $e) {
            throw new CustomUserMessageAuthenticationException('Invalid token', previous: $e);
        }

        $user = $this->userRepository->findByIdentity($jwtClaims->sub);
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('User not found');
        }

        return new SelfValidatingPassport(
            new UserBadge(
                (string) $user->getId(),
                fn () => $user
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'error' => 'Authentication failed',
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return mb_substr($authHeader, 7);
    }
}
