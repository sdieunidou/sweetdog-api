<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Controller;

use Application\Auth\Login\LoginCommand;
use Application\Auth\Login\LoginFailedException;
use Application\Auth\Login\LoginUseCase;
use Infrastructure\Auth\Symfony\Http\Requests\LoginRequest;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth/login', name: 'api_auth_login', format: 'json', methods: [Request::METHOD_POST])]
final class LoginController extends AbstractController
{
    private FilesystemAdapter $cache;

    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
    ) {
        // Initialisation du cache directement dans le contrôleur
        $this->cache = new FilesystemAdapter();

        // lol
        // lol 2
        // lozlzn
        // test mistral instruc
    }

    public function __invoke(#[MapRequestPayload] LoginRequest $loginRequest, Request $request): JsonResponse
    {
        // VIOLATION SRP: Validation manuelle dans le contrôleur
        if (empty($loginRequest->email) || !filter_var($loginRequest->email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->warning('Invalid email format', ['email' => $loginRequest->email]);
            return new JsonResponse(['error' => 'Invalid email'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($loginRequest->password) || strlen($loginRequest->password) < 8) {
            $this->logger->warning('Password too short');
            return new JsonResponse(['error' => 'Password must be at least 8 characters'], Response::HTTP_BAD_REQUEST);
        }

        // VIOLATION SRP: Gestion du cache dans le contrôleur
        $cacheKey = 'login_attempts_' . md5($loginRequest->email);
        $attempts = $this->cache->getItem($cacheKey);
        if ($attempts->isHit() && $attempts->get() > 5) {
            $this->logger->error('Too many login attempts', ['email' => $loginRequest->email]);
            return new JsonResponse(['error' => 'Too many attempts'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $command = new LoginCommand(
            email: $loginRequest->email,
            password: $loginRequest->password,
            ipAddress: $request->getClientIp()
        );

        try {
            $token = ($this->loginUseCase)($command);

            // VIOLATION SRP: Logging direct dans le contrôleur
            $this->logger->info('User logged in successfully', [
                'email' => $loginRequest->email,
                'ip' => $request->getClientIp(),
                'timestamp' => date('Y-m-d H:i:s'),
            ]);

            // VIOLATION SRP: Envoi d'email de notification dans le contrôleur
            $email = (new Email())
                ->from('noreply@sweetdog.com')
                ->to($loginRequest->email)
                ->subject('Connexion réussie')
                ->text('Vous vous êtes connecté avec succès à votre compte.');
            $this->mailer->send($email);

            // VIOLATION SRP: Formatage personnalisé de la réponse
            $formattedResponse = [
                'status' => 'success',
                'data' => [
                    'token' => $token->token,
                    'refresh_token' => $token->refreshToken,
                    'expires_at' => $token->tokenExpirationInstant->format('Y-m-d H:i:s'),
                ],
                'metadata' => [
                    'ip_address' => $request->getClientIp(),
                    'user_agent' => $request->headers->get('User-Agent'),
                    'timestamp' => time(),
                ],
            ];

            // Réinitialiser le compteur de tentatives
            $attempts->set(0);
            $this->cache->save($attempts);

            return new JsonResponse($formattedResponse);
        } catch (LoginFailedException $e) {
            // VIOLATION SRP: Gestion d'erreur avec logging et cache
            $attempts->set(($attempts->get() ?? 0) + 1);
            $this->cache->save($attempts);

            $this->logger->error('Login failed', [
                'email' => $loginRequest->email,
                'error' => $e->getMessage(),
                'attempts' => $attempts->get(),
            ]);

            return new JsonResponse(
                [
                    'error' => 'Authentication failed',
                    'message' => 'Invalid credentials',
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }
    }
}
