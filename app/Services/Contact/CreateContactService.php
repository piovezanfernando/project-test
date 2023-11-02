<?php

namespace App\Services\Contact;

use App\Repositories\ContactRepository;

class CreateContactService
{
    protected array $data;

    public function __construct(private readonly ContactRepository $repository)
    {
    }

    /**
     * Set data value to Model
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Execute create in repository
     */
    public function handle(): array
    {
        $contact = $this->repository->create($this->data);
        return $contact->toArray();
    }
}
