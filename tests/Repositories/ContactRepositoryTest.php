<?php

namespace Tests\Repositories;

use App\Models\Contact;
use App\Repositories\ContactRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Vinkla\Hashids\Facades\Hashids;
use Tests\TestCase;
use Tests\ApiTestTrait;

class ContactRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions, DatabaseMigrations;

    protected ContactRepository $contactRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->contactRepo = app(ContactRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_contact()
    {
        $contact = Contact::factory()->make()->toArray();

        $createdContact = $this->contactRepo->create($contact);

        $createdContact = $createdContact->toArray();
        $this->assertArrayHasKey('id', $createdContact);
        $this->assertNotNull(
            Contact::find(
                (int)Hashids::connection('main')->decodeHex($createdContact['id'])
                ), 'Contact with given id must be in DB');
        $this->assertModelData($contact, $createdContact);
    }

    /**
     * @test read
     */
    public function test_read_contact()
    {
        $contact = Contact::factory()->create()->toArray();

        $dbContact = $this->contactRepo->find((int)Hashids::connection('main')->decodeHex($contact['id']));

        $dbContact = $dbContact->toArray();
        $this->assertModelData($contact, $dbContact);
    }

    /**
     * @test update
     */
    public function test_update_contact()
    {
        $contact = Contact::factory()->create()->toArray();
        $fakeContact = Contact::factory()->make()->toArray();

        $updatedContact = $this->contactRepo->update($fakeContact, (int)Hashids::connection('main')->decodeHex($contact['id']));

        $this->assertModelData($fakeContact, $updatedContact);
        $dbContact = $this->contactRepo->find((int)Hashids::connection('main')->decodeHex($contact['id']));
        $this->assertModelData($fakeContact, $dbContact->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_contact()
    {
        $contact = Contact::factory()->create()->toArray();

        $resp = $this->contactRepo->delete((int)Hashids::connection('main')->decodeHex($contact['id']));

        $this->assertTrue($resp);
        $this->assertNull(Contact::find((int)Hashids::connection('main')->decodeHex($contact['id'])), 'Contact should not exist in DB');
    }
}
