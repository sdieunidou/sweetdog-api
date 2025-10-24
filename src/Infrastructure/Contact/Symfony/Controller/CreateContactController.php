<?php

declare(strict_types=1);

namespace Infrastructure\Contact\Symfony\Controller;

use Application\Contact\CreateContactUseCase;
use Infrastructure\Contact\Symfony\Http\Requests\CreateContactRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

final class CreateContactController extends AbstractController
{
    public function __construct(
        private readonly CreateContactUseCase $createContactUseCase,
    ) {
    }

    #[Route('/api/contacts', name: 'api_contact_create', methods: [Request::METHOD_POST])]
    public function __invoke(#[MapRequestPayload] CreateContactRequest $createContactRequest): JsonResponse
    {
        ($this->createContactUseCase)(
            $createContactRequest->subject,
            $createContactRequest->message
        );

        return new JsonResponse(['message' => 'Contact created successfully'], Response::HTTP_CREATED);
    }
}
