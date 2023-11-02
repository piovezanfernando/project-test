<?php

namespace Tests\Services\Contact;

use App\Models\Contact;
use App\Services\Contact\RetrieveContactService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\ApiTestTrait;

class RetrieveContactServiceTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions, DatabaseMigrations;

    protected RetrieveContactService $retrieveService;

    public function setUp() : void
    {
        parent::setUp();
        $this->retrieveService = app(RetrieveContactService::class);
    }

    /**
     * @test read
     */
    public function test_read_contact_by_service()
    {
        $contact = Contact::factory()->create()->toArray();

        $this->retrieveService->setId($contact['id']);
        $dbContact = $this->retrieveService->handle();

        $dbContact = $dbContact->toArray();
        $this->assertModelData($contact, $dbContact);
    }
}