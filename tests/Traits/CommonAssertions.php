<?php

declare(strict_types=1);

namespace Tests\Traits;

use Symfony\Component\HttpFoundation\Response;

trait CommonAssertions
{
    private array $lastResponseData = [];

    public function setLastResponseData(array $lastResponseData): self
    {
        $this->lastResponseData = $lastResponseData;

        return $this;
    }

    public function getResponseData(): array
    {
        return $this->lastResponseData;
    }

    public function getResponse(): Response
    {
        return $this->client->getResponse();
    }

    public function assertSuccess(): self
    {
        $this->testCase->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return $this;
    }

    public function assertBadRequest(): self
    {
        $this->testCase->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        return $this;
    }

    public function assertNotFound(): self
    {
        $this->testCase->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        return $this;
    }

    public function assertUnauthorized(): self
    {
        $this->testCase->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        return $this;
    }

    public function assertForbidden(): self
    {
        $this->testCase->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        return $this;
    }

    public function assertServerError(): self
    {
        $this->testCase->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        return $this;
    }

    public function assertValidationError(): self
    {
        $this->testCase->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        return $this;
    }

    public function assertSuccessResponse(array $expectedData): self
    {
        $responseData = $this->getResponseData();

        foreach ($expectedData as $key => $expectedValue) {
            $this->testCase->assertSame($expectedValue, $responseData[$key]);
        }

        return $this;
    }

    public function assertViolations(array $expectedErrors = []): self
    {
        $responseData = $this->getResponseData();

        // 1. Vérifier la structure de la réponse d'erreur
        $this->testCase->assertArrayHasKey('type', $responseData);
        $this->testCase->assertArrayHasKey('title', $responseData);
        $this->testCase->assertArrayHasKey('status', $responseData);
        $this->testCase->assertArrayHasKey('detail', $responseData);

        // 2. Vérifier la structure des violations
        $this->testCase->assertArrayHasKey('violations', $responseData);
        $this->testCase->assertIsArray($responseData['violations']);
        $this->testCase->assertGreaterThan(0, count($responseData['violations']));

        foreach ($responseData['violations'] as $violation) {
            $this->testCase->assertArrayHasKey('propertyPath', $violation);
            $this->testCase->assertArrayHasKey('title', $violation);
            $this->testCase->assertArrayHasKey('template', $violation);
            $this->testCase->assertArrayHasKey('parameters', $violation);
            $this->testCase->assertArrayHasKey('type', $violation);
        }

        // 3. Vérifier le contenu des erreurs si fourni
        if (!empty($expectedErrors)) {
            foreach ($expectedErrors as $expectedError) {
                $this->testCase->assertStringContainsString($expectedError, $responseData['detail'] ?? '');
            }
        }

        return $this;
    }

    public function assertIdIsInteger(): self
    {
        $responseData = $this->getResponseData();
        $this->testCase->assertIsInt($responseData['id']);

        return $this;
    }
}
