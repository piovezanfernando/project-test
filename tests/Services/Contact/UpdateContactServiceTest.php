<?php

namespace Tests\Services\Contact;

use App\Models\Contact;
use App\Services\Contact\UpdateContactService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Vinkla\Hashids\Facades\Hashids;
use Tests\TestCase;
use Tests\ApiTestTrait;

class UpdateContactServiceTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions, DatabaseMigrations;

    protected UpdateContactService $updateService;

    public function setUp() : void
    {
        parent::setUp();
        $this->updateService = app(UpdateContactService::class);
    }

    /**
     * @test update
     */
    public function test_update_contact_by_service()
    {
        $contact = Contact::factory()->create()->toArray();
        $fakeContact = Contact::factory()->make()->toArray();

        $this->updateService->validId($contact['id']);
        $this->updateService->setId($contact['id']);
        $this->updateService->setData($fakeContact);
        $updatedContact = $this->updateService->handle();

        $this->assertModelData($fakeContact, $updatedContact);
        $dbContact = Contact::find((int)Hashids::connection('main')->decodeHex($contact['id']));
        $this->assertModelData($fakeContact, $dbContact->toArray());
    }
}