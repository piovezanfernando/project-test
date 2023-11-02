<?php

namespace App\Services\Contact;

use App\Repositories\ContactRepository;
use Illuminate\Http\Request;

class RetrievesContactsService
{
    protected int $id;

    public function __construct(private readonly ContactRepository $repository)
    {
    }

    public function handle(Request $request): array
    {
        $contact = $this->repository->executeSearch($request);
        return $contact->toArray();
    }
}
