<?php

namespace App\Services\Contact;

use App\Repositories\ContactRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class RetrieveContactService
{
    protected int $id;

    public function __construct(private readonly ContactRepository $repository)
    {
    }

    /**
     * Set Id for model search
     */
    public function setId(string $id): void
    {
        $this->id = (int) Hashids::connection('main')->decodeHex($id);
    }

    /**
     * Execute search in model
     */
    public function handle(): Builder|Collection|Model|null
    {
        return $this->repository->find($this->id);
    }
}
