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
}