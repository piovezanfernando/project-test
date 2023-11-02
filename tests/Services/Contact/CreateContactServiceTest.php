<?php

namespace Tests\Services\Contact;

use App\Models\Contact;
use App\Services\Contact\CreateContactService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Vinkla\Hashids\Facades\Hashids;
use Tests\TestCase;
use Tests\ApiTestTrait;

class CreateContactServiceTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions, DatabaseMigrations;

    protected CreateContactService $createService;

    public function setUp() : void
    {
        parent::setUp();
        $this->createService = app(CreateContactService::class);
    }

    /**
     * @test create
     */
    public function test_create_contact_by_service()
    {
        $data = Contact::factory()->make()->toArray();

        $this->createService->setData($data);
        $createdContact = $this->createService->handle();

        $this->assertArrayHasKey('id', $createdContact);
        $this->assertNotNull($createdContact['id'], 'Created Contact must have id specified');
        $this->assertNotNull(
            Contact::find(
                (int)Hashids::connection('main')->decodeHex($createdContact['id'])
                ), 'Contact with given id must be in DB');
        $this->assertModelData($data, $createdContact);
    }
}