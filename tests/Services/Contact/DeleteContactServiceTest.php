<?php

namespace Tests\Services\Contact;

use App\Models\Contact;
use App\Services\Contact\DeleteContactService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\ApiTestTrait;

class DeleteContactServiceTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions, DatabaseMigrations;

    protected DeleteContactService $deleteService;

    public function setUp() : void
    {
        parent::setUp();
        $this->deleteService = app(DeleteContactService::class);
    }

    /**
     * @test delete
     */
    public function test_delete_contact_by_service()
    {
        $data = Contact::factory()->create()->toArray();

        $this->deleteService->setId($data['id']);
        $deleteContact = $this->deleteService->handle();

        $this->assertTrue($deleteContact['code'] === 200);
        $this->assertNull(Contact::find($data['id']), 'Contact should not exist in DB');
    }
}