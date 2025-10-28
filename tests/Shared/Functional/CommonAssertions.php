<?php

declare(strict_types=1);

namespace Tests\Shared\Functional;

use Symfony\Component\HttpFoundation\Response;

trait CommonAssertions
{
    protected function assertHttpSuccess(): self
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        return $this;
    }

    protected function assertHttpCreated(): self
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return $this;
    }

    protected function assertHttpBadRequest(): self
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        return $this;
    }

    protected function assertHttpNotFound(): self
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        return $this;
    }

    protected function assertHttpUnauthorized(): self
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        return $this;
    }

    protected function assertHttpForbidden(): self
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        return $this;
    }

    protected function assertHttpServerError(): self
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        return $this;
    }

    protected function assertHttpValidationError(): self
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        return $this;
    }

    protected function assertSuccessResponse(array $expectedData, ?array $responseData = null): self
    {
        if (null === $responseData) {
            $responseData = $this->getResponseData();
        }

        foreach ($expectedData as $key => $expectedValue) {
            $this->assertSame($expectedValue, $responseData[$key]);
        }

        return $this;
    }

    protected function assertViolations(array $expectedErrors = [], ?array $responseData = null): self
    {
        if (null === $responseData) {
            $responseData = $this->getResponseData();
        }

        $this->assertArrayHasKey('type', $responseData);
        $this->assertArrayHasKey('title', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('detail', $responseData);

        $this->assertArrayHasKey('violations', $responseData);
        $this->assertIsArray($responseData['violations']);
        $this->assertGreaterThan(0, count($responseData['violations']));

        foreach ($responseData['violations'] as $violation) {
            $this->assertArrayHasKey('propertyPath', $violation);
            $this->assertArrayHasKey('title', $violation);
            $this->assertArrayHasKey('template', $violation);
            $this->assertArrayHasKey('parameters', $violation);
            $this->assertArrayHasKey('type', $violation);
        }

        if (!empty($expectedErrors)) {
            foreach ($expectedErrors as $expectedError) {
                $this->assertStringContainsString($expectedError, $responseData['detail'] ?? '');
            }
        }

        return $this;
    }

    protected function assertIdIsInteger(?array $responseData = null): self
    {
        if (null === $responseData) {
            $responseData = $this->getResponseData();
        }
        $this->assertIsInt($responseData['id']);

        return $this;
    }
}
