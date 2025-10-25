<?php

declare(strict_types=1);

namespace Tests\Contact\Functional\CreateContact;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Contact\Functional\ContactApiEndpoint;
use Tests\Contact\Functional\ContactRequestBuilder;

class CreateContactApiTest extends WebTestCase
{
    public function testCreateContactSuccess(): void
    {
        $client = static::createClient();
        $apiPage = new ContactApiEndpoint($client, $this);

        $apiPage->createContact((new ContactRequestBuilder())->withValidData()->build())
                ->assertSuccess()
                ->assertSuccessResponse([
                    'subject' => 'Valid Subject',
                    'message' => 'Valid message with enough characters',
                ])
                ->assertIdIsInteger();
    }

    public function testCreateContactWithEmptyFields(): void
    {
        $client = static::createClient();
        $apiPage = new ContactApiEndpoint($client, $this);

        $apiPage->createContact((new ContactRequestBuilder())->withEmptyFields()->build())
                ->assertValidationError()
                ->assertViolations([
                    'Le sujet ne peut pas être vide',
                    'Le message ne peut pas être vide',
                ]);
    }

    public function testCreateContactWithInvalidJson(): void
    {
        $client = static::createClient();
        $apiPage = new ContactApiEndpoint($client, $this);

        $apiPage->createContactWithInvalidJson()
                ->assertBadRequest();
    }

    #[DataProviderExternal(CreateContactValidationDataProvider::class, 'invalidSubjects')]
    public function testCreateContactWithInvalidSubject(string $subject, string $expectedError): void
    {
        $client = static::createClient();
        $apiPage = new ContactApiEndpoint($client, $this);

        $apiPage->createContact((new ContactRequestBuilder())->withSubject($subject)->build())
                ->assertValidationError()
                ->assertViolations([$expectedError]);
    }

    #[DataProviderExternal(CreateContactValidationDataProvider::class, 'invalidMessages')]
    public function testCreateContactWithInvalidMessage(string $message, string $expectedError): void
    {
        $client = static::createClient();
        $apiPage = new ContactApiEndpoint($client, $this);

        $apiPage->createContact((new ContactRequestBuilder())->withMessage($message)->build())
                ->assertValidationError()
                ->assertViolations([$expectedError]);
    }

    #[DataProviderExternal(CreateContactValidationDataProvider::class, 'multipleValidationErrors')]
    public function testCreateContactWithMultipleValidationErrors(array $data, array $expectedErrors): void
    {
        $client = static::createClient();
        $apiPage = new ContactApiEndpoint($client, $this);

        $apiPage->createContact($data)
                ->assertValidationError()
                ->assertViolations($expectedErrors);
    }

    public function testCreateContactErrorResponseStructure(): void
    {
        $client = static::createClient();
        $apiPage = new ContactApiEndpoint($client, $this);

        $apiPage->createContact((new ContactRequestBuilder())->withEmptyFields()->build())
                ->assertValidationError()
                ->assertViolations();
    }

    #[DataProviderExternal(CreateContactValidationDataProvider::class, 'boundaryValues')]
    public function testCreateContactWithBoundaryValues(string $subject, string $message): void
    {
        $client = static::createClient();
        $apiPage = new ContactApiEndpoint($client, $this);

        $apiPage->createContact((new ContactRequestBuilder())->withSubject($subject)->withMessage($message)->build())
                ->assertSuccess()
                ->assertSuccessResponse([
                    'subject' => $subject,
                    'message' => $message,
                ])
                ->assertIdIsInteger();
    }

    public function testCreateContactWithValidSpecialCharacters(): void
    {
        $client = static::createClient();
        $apiPage = new ContactApiEndpoint($client, $this);

        $apiPage->createContact((new ContactRequestBuilder())->withSpecialCharacters()->build())
                ->assertSuccess()
                ->assertSuccessResponse([
                    'subject' => 'Test Subject with valid chars 123, 456! 789?',
                    'message' => 'Test Message with valid special characters: 123, 456! 789? ; "quotes" and (parentheses)',
                ])
                ->assertIdIsInteger();
    }
}
