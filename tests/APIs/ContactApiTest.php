<?php

namespace Tests\APIs;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Contact;

class ContactApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions, DatabaseMigrations;

    /**
     * @test
     */
    public function test_create_contact()
    {
        $contact = Contact::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/contacts', $contact
        );

        $this->assertApiResponse($contact);
    }

    /**
     * @test
     */
    public function test_read_contact()
    {
        $contact = Contact::factory()->create()->toArray();

        $this->response = $this->json(
            'GET',
            '/api/contacts/'.$contact['id']
        );

        $this->assertApiResponse($contact);
    }

    /**
     * @test
     */
    public function test_update_contact()
    {
        $contact = Contact::factory()->create()->toArray();
        $editedContact = Contact::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/contacts/'.$contact['id'],
            $editedContact
        );

        $this->assertApiResponse($editedContact);
    }

    /**
     * @test
     */
    public function test_delete_contact()
    {
        $contact = Contact::factory()->create()->toArray();

        $this->response = $this->json(
            'DELETE',
             '/api/contacts/'.$contact['id']
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/contacts/'.$contact['id']
        );

        $this->response->assertStatus(404);
    }
}
