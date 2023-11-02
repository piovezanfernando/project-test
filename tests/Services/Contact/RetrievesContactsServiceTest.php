<?php

namespace Tests\Services\Contact;

use App\Models\Contact;
use App\Services\Contact\RetrievesContactsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\ApiTestTrait;

class RetrievesContactsServiceTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions, DatabaseMigrations;

    protected RetrievesContactsService $retrievesService;

    public function setUp() : void
    {
        parent::setUp();
        $this->retrievesService = app(RetrievesContactsService::class);
    }

    /**
     * @test read all
     */
    public function test_read_all_contact_by_service()
    {
        Contact::factory()->create();

        $req = new Request(['limit' => 1]);
        $dbContact = $this->retrievesService->handle($req);

        $this->assertArrayHasKey('data', $dbContact);
    }
}