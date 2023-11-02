<?php

namespace App\Services\Contact;

use App\Repositories\ContactRepository;
use Vinkla\Hashids\Facades\Hashids;

class DeleteContactService
{
    protected int $id;

    public function __construct(private readonly ContactRepository $repository)
    {
    }

    public function setId(string $id): void
    {
        $this->id = (int) Hashids::connection('main')->decodeHex($id);
    }

    public function handle(): array
    {
        return $this->repository->deleteOrUndelete($this->id);
    }
}
