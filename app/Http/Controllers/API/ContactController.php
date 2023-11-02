<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\API\Contact\CreateContactAPIRequest;
use App\Http\Requests\API\Contact\UpdateContactAPIRequest;
use App\Models\Contact;
use App\Services\Contact\CreateContactService;
use App\Services\Contact\DeleteContactService;
use App\Services\Contact\RetrieveContactService;
use App\Services\Contact\RetrievesContactsService;
use App\Services\Contact\UpdateContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ContactController
 * @package App\Http\Controllers\API
 * @group Contacts
 * Endpoint de gerenciamento de Contacts
 */
class ContactController extends AppBaseController
{
    public function __construct(
        private readonly CreateContactService $createService,
        private readonly DeleteContactService $deleteService,
        private readonly RetrieveContactService $retrieveService,
        private readonly RetrievesContactsService $retrievesService,
        private readonly UpdateContactService $updateService,
    ) {
    }

    /**
     * Display a listing of the Contacts.
     * @authenticated
     * @queryParam limit integer Quantidade de registros retornado na consulta. Exemplo 15 No-example
     * @queryParam page integer Página a ser exibida na consulta. Exemplo 1 No-example
     * @queryParam order string Campo para ordenação do retorno. Exemplo name No-example
     * @queryParam fields string Informe a seleção de campos que devem retornar da
     *  consulta separados por virgula. Exemplo id, name No-example
     * @queryParam search string Pesquise por qualquer campo, ao usar este campo as
     *  outras consultas serão desconsideradas. Este campo usa uma consulta OR em todos
     *  os campos da tabela e das relações, portanto pode ser uma busca lenta em sua execução No-example
     * @queryParam created_by[] string[] Pesquise pela coluna da tabela relacionada. Exemplo tabela[coluna]. No-example
     * @queryParam updated_by[?] string[] Pesquise pela coluna da tabela relacionada. Exemplo tabela[coluna]. No-example
     * @queryParam start_created_at string Busca por data inicial de criação. Se enviado sozinho faz busca exata. Exemplo 2021-01-30 No-example
     * @queryParam end_created_at string Busca por data final de criação, quando combinada com o start_created_at
     *  é efetuada uma busca com Between. Exemplo 2021-01-30 No-example
     * @queryParam start_updated_at string Busca por data inicial de criação. Se enviado sozinho faz busca exata. Exemplo 2021-01-30 No-example
     * @queryParam end_updated_at string Busca por data final de criação, quando combinada com o start_created_at
     *  é efetuada uma busca com Between. Exemplo 2021-01-30 No-example
     * @queryParam hide_relation string Informe o nome da relação que deverá ser ocultada na consulta. No-Example
     * @responseFile status=401 storage/response/error/401.json
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->retrievesService->handle($request);

        return $this->sendResponse(
            $data,
            __('messages.retrieved', ['model' => __('models/contacts.plural')])
        );
    }

    /**
     * Store a newly created Contact in storage.
     * POST /contacts
     * @responseFile status=401 storage/response/error/401.json
     */
    public function store(CreateContactAPIRequest $request): JsonResponse
    {
        $this->createService->setData($request->all());

        $contact = $this->createService->handle();

        return $this->sendResponse(
            $contact,
            __('messages.saved', ['model' => __('models/contacts.singular')])
        );
    }

    /**
     * Display the specified Contact.
     * GET|HEAD /contacts/{id}
     * @responseFile status=401 storage/response/error/401.json
     * @responseFile status=404 storage/response/error/404.json {"message": "Contact not found"}
     */
    public function show(string $id): JsonResponse
    {
        $this->retrieveService->setId($id);

        /** @var Contact $contact */
        $contact = $this->retrieveService->handle();

        if (empty($contact)) {
            return $this->sendError(
                __('messages.not_found', ['model' => __('models/contacts.singular')])
            );
        }
        return $this->sendResponse(
            $contact->toArray(),
            __('messages.retrieved', ['model' => __('models/contacts.singular')])
        );
    }

    /**
     * Update the specified Contact in storage.
     * PUT/PATCH /contacts/{id}
     * @responseFile status=401 storage/response/error/401.json
     * @responseFile status=404 storage/response/error/404.json {"message": "Contact not found"}
     */
    public function update(string $id, UpdateContactAPIRequest $request): JsonResponse
    {
        /** @var Contact $contact */
        $contact = $this->updateService->validId($id);

        if (empty($contact)) {
            return $this->sendError(
                __('messages.not_found', ['model' => __('models/contacts.singular')])
            );
        }

        $this->updateService->setId($id);
        $this->updateService->setData($request->all());
        $contact = $this->updateService->handle();

        return $this->sendResponse(
            $contact,
            __('messages.updated', ['model' => __('models/contacts.singular')])
        );
    }

    /**
     * Remove the specified Contact from storage.
     * DELETE /contacts/{id}
     * @throws \Exception
     * @responseFile status=401 storage/response/error/401.json
     * @responseFile status=404 storage/response/error/404.json {"message": "Contact not found"}
     */
    public function destroy(string $id): JsonResponse
    {
        $this->deleteService->setId($id);

        return $this->response($this->deleteService->handle());
    }
}
