<?php

declare(strict_types=1);

namespace Infrastructure\Contact\Symfony\Controller;

use Application\Contact\CreateContact\CreateContactCommand;
use Application\Contact\CreateContact\CreateContactUseCase;
use Infrastructure\Contact\Symfony\Http\Requests\CreateContactRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/contacts', name: 'api_contact_create', format: 'json', methods: [Request::METHOD_POST])]
final class CreateContactController extends AbstractController
{
    public function __construct(
        private readonly CreateContactUseCase $createContactUseCase,
        private readonly SerializerInterface $serializer,
        private readonly ObjectMapperInterface $objectMapper,
    ) {}

    public function __invoke(#[MapRequestPayload] CreateContactRequest $createContactRequest): JsonResponse
    {
        $command = $this->objectMapper->map($createContactRequest, CreateContactCommand::class);

        $contact = ($this->createContactUseCase)($command);

        return new JsonResponse(
            $this->serializer->serialize($contact, 'json', ['groups' => ['contact:read']]),
            Response::HTTP_CREATED,
            json: true
        );
    }
}
