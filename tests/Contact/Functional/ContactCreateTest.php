<?php

declare(strict_types=1);

namespace Tests\Contact\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ContactCreateTest extends WebTestCase
{
    public function testCreateContactSuccess(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'Test Subject',
            'message' => 'Test Message'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('Test Subject', $responseData['subject']);
        $this->assertSame('Test Message', $responseData['message']);
        $this->assertNotNull($responseData['id']);
        $this->assertIsInt($responseData['id']);
    }
    
    public function testCreateContactWithEmptyFields(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => '',
            'message' => ''
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    
    public function testCreateContactWithInvalidJson(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/contacts', content: 'invalid json');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // Tests de validation pour le champ 'subject'
    
    public function testCreateContactWithSubjectTooShort(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'AB', // 2 caractères, minimum 3 requis
            'message' => 'Test Message with enough characters'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertStringContainsString('Le sujet doit contenir au moins 3 caractères', $responseData['detail']);
    }
    
    public function testCreateContactWithSubjectTooLong(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => str_repeat('A', 101), // 101 caractères, maximum 100
            'message' => 'Test Message with enough characters'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertStringContainsString('Le sujet ne peut pas dépasser 100 caractères', $responseData['detail']);
    }
    
    public function testCreateContactWithSubjectInvalidCharacters(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'Test@Subject#Invalid', // Caractères non autorisés
            'message' => 'Test Message with enough characters'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertStringContainsString('Le sujet contient des caractères non autorisés', $responseData['detail']);
    }

    // Tests de validation pour le champ 'message'
    
    public function testCreateContactWithMessageTooShort(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'Test Subject',
            'message' => 'Short' // 5 caractères, minimum 10 requis
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertStringContainsString('Le message doit contenir au moins 10 caractères', $responseData['detail']);
    }
    
    public function testCreateContactWithMessageTooLong(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'Test Subject',
            'message' => str_repeat('A', 1001) // 1001 caractères, maximum 1000
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertStringContainsString('Le message ne peut pas dépasser 1000 caractères', $responseData['detail']);
    }
    
    public function testCreateContactWithMessageInvalidCharacters(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'Test Subject',
            'message' => 'Test Message with invalid characters: @#$%^&*' // Caractères non autorisés
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertStringContainsString('Le message contient des caractères non autorisés', $responseData['detail']);
    }

    // Tests de validation combinés
    
    public function testCreateContactWithMultipleValidationErrors(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'AB', // Trop court
            'message' => 'Short' // Trop court
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertStringContainsString('Le sujet doit contenir au moins 3 caractères', $responseData['detail']);
        $this->assertStringContainsString('Le message doit contenir au moins 10 caractères', $responseData['detail']);
    }

    // Tests de la structure de réponse d'erreur
    
    public function testCreateContactErrorResponseStructure(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => '', // Vide
            'message' => '' // Vide
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        
        // Vérifier la structure de la réponse d'erreur
        $this->assertArrayHasKey('type', $responseData);
        $this->assertArrayHasKey('title', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('detail', $responseData);
        $this->assertArrayHasKey('violations', $responseData);
        
        $this->assertSame('https://symfony.com/errors/validation', $responseData['type']);
        $this->assertSame('Validation Failed', $responseData['title']);
        $this->assertSame(422, $responseData['status']);
        
        // Vérifier que les violations contiennent les bonnes informations
        $this->assertIsArray($responseData['violations']);
        $this->assertGreaterThan(0, count($responseData['violations']));
        
        foreach ($responseData['violations'] as $violation) {
            $this->assertArrayHasKey('propertyPath', $violation);
            $this->assertArrayHasKey('title', $violation);
            $this->assertArrayHasKey('template', $violation);
            $this->assertArrayHasKey('parameters', $violation);
            $this->assertArrayHasKey('type', $violation);
        }
    }

    // Tests de cas limites
    
    public function testCreateContactWithSubjectExactMinimumLength(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'ABC', // Exactement 3 caractères
            'message' => 'Test Message with enough characters'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
    
    public function testCreateContactWithSubjectExactMaximumLength(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => str_repeat('A', 100), // Exactement 100 caractères
            'message' => 'Test Message with enough characters'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
    
    public function testCreateContactWithMessageExactMinimumLength(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'Test Subject',
            'message' => '1234567890' // Exactement 10 caractères
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
    
    public function testCreateContactWithMessageExactMaximumLength(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'Test Subject',
            'message' => str_repeat('A', 1000) // Exactement 1000 caractères
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    // Tests de caractères autorisés
    
    public function testCreateContactWithValidSpecialCharacters(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/contacts', [
            'subject' => 'Test Subject with valid chars 123, 456! 789?', // Pas de : dans le subject
            'message' => 'Test Message with valid special characters: 123, 456! 789? ; "quotes" and (parentheses)'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
}