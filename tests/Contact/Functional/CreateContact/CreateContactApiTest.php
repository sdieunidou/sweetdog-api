<?php

declare(strict_types=1);

namespace Tests\Contact\Functional\CreateContact;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Contact\Functional\ContactApiClient;
use Tests\Contact\Functional\ContactRequestBuilder;
use Tests\Shared\Functional\CommonAssertions;

class CreateContactApiTest extends WebTestCase
{
    use CommonAssertions;

    private function createApiClient(): ContactApiClient
    {
        return new ContactApiClient(static::createClient(), $this);
    }

    public function testCreateContactSuccess(): void
    {
        $client = $this->createApiClient()
            ->createContact((new ContactRequestBuilder())->withValidData()->build())
        ;

        $responseData = $client->getResponseData();

        $this
            ->assertHttpCreated()
            ->assertSuccessResponse([
                'subject' => 'Valid Subject',
                'message' => 'Valid message with enough characters',
            ], $responseData)
            ->assertIdIsInteger($responseData);
    }

    public function testCreateContactWithEmptyFields(): void
    {
        $client = $this->createApiClient()
            ->createContact((new ContactRequestBuilder())->withEmptyFields()->build())
        ;

        $responseData = $client->getResponseData();

        $this
            ->assertHttpValidationError()
            ->assertViolations([
                'Le sujet ne peut pas être vide',
                'Le message ne peut pas être vide',
            ], $responseData);
    }

    #[DataProviderExternal(CreateContactValidationDataProvider::class, 'invalidSubjects')]
    public function testCreateContactWithInvalidSubject(string $subject, string $expectedError): void
    {
        $client = $this->createApiClient()
            ->createContact((new ContactRequestBuilder())->withSubject($subject)->build())
        ;

        $responseData = $client->getResponseData();

        $this
            ->assertHttpValidationError()
            ->assertViolations([$expectedError], $responseData);
    }

    #[DataProviderExternal(CreateContactValidationDataProvider::class, 'invalidMessages')]
    public function testCreateContactWithInvalidMessage(string $message, string $expectedError): void
    {
        $client = $this->createApiClient()
            ->createContact((new ContactRequestBuilder())->withMessage($message)->build())
        ;

        $responseData = $client->getResponseData();

        $this
            ->assertHttpValidationError()
            ->assertViolations([$expectedError], $responseData);
    }

    #[DataProviderExternal(CreateContactValidationDataProvider::class, 'multipleValidationErrors')]
    public function testCreateContactWithMultipleValidationErrors(array $data, array $expectedErrors): void
    {
        $client = $this->createApiClient()
            ->createContact($data)
        ;

        $responseData = $client->getResponseData();

        $this
            ->assertHttpValidationError()
            ->assertViolations($expectedErrors, $responseData);
    }

    public function testCreateContactErrorResponseStructure(): void
    {
        $client = $this->createApiClient()
            ->createContact((new ContactRequestBuilder())->withEmptyFields()->build())
        ;

        $responseData = $client->getResponseData();

        $this
            ->assertHttpValidationError()
            ->assertViolations([], $responseData);
    }

    #[DataProviderExternal(CreateContactValidationDataProvider::class, 'boundaryValues')]
    public function testCreateContactWithBoundaryValues(string $subject, string $message): void
    {
        $client = $this->createApiClient()
            ->createContact((new ContactRequestBuilder())->withSubject($subject)->withMessage($message)->build())
        ;

        $responseData = $client->getResponseData();

        $this
            ->assertHttpCreated()
            ->assertSuccessResponse([
                'subject' => $subject,
                'message' => $message,
            ], $responseData)
            ->assertIdIsInteger($responseData);
    }

    public function testCreateContactWithValidSpecialCharacters(): void
    {
        $client = $this->createApiClient()
            ->createContact((new ContactRequestBuilder())->withSpecialCharacters()->build())
        ;

        $responseData = $client->getResponseData();

        $this
            ->assertHttpCreated()
            ->assertSuccessResponse([
                'subject' => 'Test Subject with valid chars 123, 456! 789?',
                'message' => 'Test Message with valid special characters: 123, 456! 789? ; "quotes" and (parentheses)',
            ], $responseData)
            ->assertIdIsInteger($responseData);
    }
}
