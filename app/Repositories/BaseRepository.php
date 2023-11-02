<?php

namespace App\Repositories;

use App\Models\BaseModel;
use Illuminate\Container\Container as Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

abstract class BaseRepository
{
    protected Model $model;

    protected Application $app;

    protected Builder|BaseModel $baseQuery;

    /** @var array campos que podem ser usados na busca */
    protected array $fieldSearchable = [];

    /** @var array campos que fazem parte do índice FullText da tabela */
    protected array $fieldsFullText = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeModel();
        $this->baseQuery = $this->newBaseQuery();
//        if ($this->model->hasCompanyId()) {
//            $this->verifyLimiter();
//        }
    }

    /**
     * Make Model instance
     */
    public function makeModel(): Model
    {
        $baseModel = $this->app->make($this->model());

        if (!$baseModel instanceof Model) {
            throw new \Exception('Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model');
        }

        $this->model = $baseModel;
        return $this->model;
    }

    /**
     * Configure the Model
     */
    abstract public function model(): string|BaseModel;

    public function newBaseQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Create model record
     */
    public function create(array $input): Model|null
    {
        $baseModel = $this->model->newInstance($input);

        $baseModel->save();

        return $baseModel;
    }

    /**
     * Atualiza o registro da model pelo Id
     */
    public function update(array $input, int $id): array
    {
        $baseModel = $this->find($id);

        $baseModel->fill($input);

        $baseModel->save();

        return $baseModel->toArray();
    }

    /**
     * Find model record for given id
     */
    public function find(int|string $id): Builder|Collection|Model|null
    {
        $query = $this->newBaseQuery();

        return $query->find($id);
    }

    /**
     * Deleta ou restaura o registro da model
     */
    public function deleteOrUndelete(int $id): array
    {
        /** @var BaseModel $baseModel */
        $baseModel = $this->find($id);

        if (is_null($baseModel)) {
            return [
                'code' => 404,
                'message' => __($this->getModelName()).' not found'
            ];
        }

        if (!empty($baseModel->deleted_at)) {
            if (!is_null($baseModel->deleted_at)) {
                $baseModel->restore();
                return [
                    'code' => 200,
                    'message' => __($this->getModelName()).' successfully reactivated'
                ];
            }
        }

        $baseModel->delete();
        return [
            'code' => 200,
            'message' => __($this->getModelName()).' successfully deactivated'
        ];
    }

    public function getModelName()
    {
        return Str::singular(Str::studly($this->model->getTable()));
    }

    /**
     * Deleta o registro da model
     */
    public function delete(int $id): bool|null
    {
        $query = $this->newBaseQuery();

        $baseModel = $query->findOrFail($id);

        return $baseModel->delete();
    }

    /**
     * Adiciona um where na query padrão
     */
    public function findBy(
        array|string|\Closure $column,
        mixed $value,
        string $operator = '=',
        string $boolean = 'and'
    ): Builder {
        $query = $this->newBaseQuery();

        return $query->where($column, $operator, $value, $boolean);
    }

    /**
     * Busca todos os models do sistema que estão na pasta Models
     */
    public function getModels(): array
    {
        $out = [];
        $outNames = [];
        $path = app_path().'/Models';
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') {
                continue;
            }
            $filename = $path.'/'.$result;
            if (is_dir($filename)) {
                continue;
            }
            $out[] = substr($filename, 0, -4);
        }
        foreach ($out as $value) {
            if (!str_contains($value, 'BaseModel')) {
                $outNames[] = [
                    'value' => Str::snake(Str::plural((array_reverse(explode('/', $value))[0]))),
                    'text' => trans_choice(
                        'messages.'.array_reverse(explode('/', $value))[0],
                        0
                    )
                ];
            }
        }
        return $outNames;
    }

    /**
     * Montar o array para sincronizar na tabela relacionada
     * montagem obrigatória para ManyToMany
     */
    public function mountValueRelation(array $input, string $fieldsInsert): string|array
    {
        $type = '';
        foreach ($input as $value) {
            if (empty($type)) {
                $type = [$value[$fieldsInsert]];
            } else {
                $type[] = $value[$fieldsInsert];
            }
        }

        return $type;
    }

    /**
     * Cria a estrutura para sincronizar a tabela de ManyToMany
     */
    public function createSync(array $input, string $relation): array
    {
        $syncs = [];
        foreach ($input[Str::Plural($relation)] as $value) {
            $syncs[] = $value['id'];
        }
        return $syncs;
    }

    /**
     * Função para sincronizar dados das tabelas relacionadas
     * seria o mesmo que o sync mas para relações hasMany
     */
    public function syncHasMany(array $input, Model $baseModel): void
    {
        foreach ($this->model->getRelationsBySearch() as $relation) {
            $id = [];
            if (isset($input[Str::snake($relation)]) && $baseModel->{$relation}() instanceof HasMany) {
                foreach ($input[Str::snake($relation)] as $value) {
                    $idInserted = $baseModel->{$relation}()->updateOrCreate(['id' => $value['id'] ?? null], $value);
                    $id[] = $idInserted->id;
                }
                $modelRelation = (new \ReflectionClass(
                    get_class($this->model->{Str::ucfirst($relation)}()->getRelated())
                ))
                    ->newInstanceWithoutConstructor()->newQuery();
                $modelRelation
                    ->whereNotIn('id', array_filter($id))
                    ->where(Str::singular($this->model->getTable()).'_id', $baseModel->id);
                foreach ($modelRelation->get() as $value) {
                    if (!empty($value)) {
                        (new \ReflectionClass(get_class($this->model->{Str::ucfirst($relation)}()->getRelated())))
                            ->newInstanceWithoutConstructor()->newQuery()->find($value->id)->delete();
                    }
                }
            }
        }
    }

    /**
     * Função que faz a iteração dos dados
     * para inserir dados na tabelas relacionadas
     */
    public function variousCreateMany(array $input, Model $baseModel): void
    {
        foreach ($this->model->getRelationsBySearch() as $relation) {
            if (isset($input[Str::snake($relation)]) && $baseModel->{$relation}() instanceof HasMany) {
                $baseModel->{$relation}()->createMany($input[Str::snake($relation)]);
            }
        }
    }

    /**
     * Método para executar limpeza de cache de qualquer model passada por parametro
     */
    public function flushCache(Request $request): array
    {
        $ret = [];
        foreach ($request->get('models') as $model) {
            $class = App::make('\\App\\Models\\'.$model['name']);
            if ($class->cacheFor) {
                try {
                    $class->flushQueryCache();
                    $ret[$model['name']] = 'Limpeza de cache executado com sucesso';
                } catch (\Throwable $e) {
                    $ret[$model['name']] = $e;
                }
            } else {
                $ret[$model['name']] = 'Não há cache para ser limpo';
            }
        }
        return $ret;
    }

    /**
     * Executa a busca de acordo com os dados do recebidos
     */
    public function executeSearch(Request $request): LengthAwarePaginator
    {
        if ($request->exists('search')) {
            $this->advancedSearch($request);
        } else {
            $this->findAllFieldsAnd($request);
        }

        $this->orderByRaw($request->get('order'), $request->get('direction'));

        return $this->paginate($request->get('limit'));
    }

    /**
     * Busca em todos os campos da tabela pela string enviada.
     * função utiliza OR por padrão
     */
    public function advancedSearch(Request $request): Builder|Model
    {
        $this->verifyActive($request);

        $this->baseQuery->whereFullText($this->fieldsFullText, $request->get('search'));

        return $this->baseQuery;
    }

    /**
     * Verifica se esta filtrando registro ativo/inativo
     */
    public function verifyActive(Request $request): void
    {
        if (!in_array(SoftDeletes::class, class_uses_recursive($this->baseQuery))) {
            return;
        }

        if ($request->exists('is_active')) {
            if (!$request->boolean('is_active')) {
                $this->baseQuery->onlyTrashed();
            }
        } else {
            $this->baseQuery->withTrashed();
        }
    }

    /**
     * Add a basic where clause to the query.
     */
    public function findAllFieldsAnd(Request $request): void
    {
        $inputs = $request->all();

        $this->verifyActive($request);

        $this->mountFieldsToSelect($request);

        $this->mountSelectToDates($request);

        $this->getWherehas($request, 'AND');

        $this->hideWith($request);

        foreach ($inputs as $key => $value) {
            if (!in_array(Str::camel($key), $this->model->getRelationsBySearch())) {
                $type = $this->model()::getFieldType($key);
                if ($type) {
                    if ($type == 'string') {
                        $this->baseQuery->where($key, 'like', '%'.$value.'%');
                    } else {
                        if (count(explode('-', $value, 2)) > 1 && !strtotime($value)) {
                            $this->baseQuery->whereBetween(
                                $key,
                                [
                                    explode(':', $value, 2)[0],
                                    explode(':', $value, 2)[1]
                                ]
                            );
                        } else {
                            $this->baseQuery->where($key, $inputs['operator'][$key] ?? '=', $value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Retrieve all records with given filter criteria
     */
    public function all(
        array $search = [],
        int|null $skip = null,
        int|null $limit = null,
        array $columns = ['*']
    ): Builder|Collection {
        $query = $this->allQuery($search, $skip, $limit);

        return $query->get($columns);
    }

    /**
     * Build a query for retrieving all records.
     */
    public function allQuery(array $search = [], int|null $skip = null, int|null $limit = null): Builder
    {
        $query = $this->newBaseQuery();

        if (count($search)) {
            foreach ($search as $key => $value) {
                if (in_array($key, $this->getFieldsSearchable())) {
                    $query->where($key, $value);
                }
            }
        }

        if (!is_null($skip)) {
            $query->skip($skip);
        }

        if (!is_null($limit)) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Get searchable fields array
     */
    abstract public function getFieldsSearchable(): array;

    /**
     * Monta os campos passados por parametros para o select
     * remove os campos que não fazem parte da Model para evitar quebra de SQL
     */
    protected function mountFieldsToSelect(Request $request): void
    {
        if ($request->exists('fields')) {
            $fields = explode(',', $request->get('fields'));
            foreach ($fields as $key => $field) {
                if (trim($field) == 'id') {
                    $fields[$key] = $this->model->getTable().'.id';
                }
                if (!array_key_exists(trim($field), $this->model->getCasts())) {
                    unset($fields[$key]);
                }
            }
            $this->baseQuery->select(array_map('trim', $fields));
        }
    }

    /**
     * Monta o filtro por data tanto com Between como busca direta
     */
    protected function mountSelectToDates(Request $request): void
    {
        if ($request->exists('start_created_at')) {
            if ($request->exists('end_created_at')) {
                $this->baseQuery->whereBetween(
                    'created_at',
                    [
                        $request->get('start_created_at').' 00:00:00',
                        $request->get('end_created_at').' 23:59:00'
                    ]
                );
            } else {
                $this->baseQuery->whereDate(
                    'created_at',
                    $request->get('start_created_at')
                );
            }
        }
        if ($request->exists('start_updated_at')) {
            if ($request->exists('end_updated_at')) {
                $this->baseQuery->whereBetween(
                    'updated_at',
                    [
                        $request->get('start_updated_at').' 00:00:00',
                        $request->get('end_updated_at').' 23:59:00'
                    ]
                );
            } else {
                $this->baseQuery->whereDate(
                    'updated_at',
                    $request->get('start_updated_at')
                );
            }
        }
    }

    /**
     * Função responsável por montar o where para tabelas relacionadas
     * de acordo com os parâmetros
     */
    public function getWherehas(Request $request, string $type): void
    {
        if ($type == 'AND') {
            foreach ($this->model->getRelationsBySearch() as $relation) {
                if (in_array(Str::snake($relation), $request->keys())) {
                    /** @var BaseModel $classRelation */
                    $classRelation = get_class($this->model->{Str::ucfirst($relation)}()->getRelated());
                    foreach ($request->get(Str::snake($relation)) as $key => $field) {
                        $this->baseQuery->whereHas($relation, function ($query) use ($key, $field, $classRelation) {
                            $type = $classRelation::getFieldType($key);
                            if ($type) {
                                if ($type == 'string') {
                                    $query->where($key, 'like', '%'.$field.'%');
                                } else {
                                    $query->where($key, $field);
                                }
                            }
                        });
                    }
                }
            }
        } else {
            foreach ($this->model->getRelationsBySearch() as $relation) {
                $this->baseQuery->orWhereHas($relation, function ($query) use ($request) {
                    $query->Where('name', 'like', '%'.$request->get('search').'%');
                });
            }
        }
    }

    /**
     * Remove a relação da query executada
     */
    protected function hideWith(Request $request): void
    {
        $relations = explode(',', $request->get('hide_relation'));
        $hide = [];

        if ($request->exists('hide_relation')) {
            foreach ($relations as $relation) {
                $hide[] = $relation;
            }
        }
        $this->baseQuery->without($hide);
    }

    /**
     * Insere ordenação a consulta.
     * Se houver valor insere o valor e a direção
     * caso contrário ordena por Id desc
     */
    protected function orderByRaw(string|null $order, string|null $dir): void
    {
        $this->baseQuery->orderByRaw($order ?? 'id'.' '.$dir ?? 'DESC');
    }

    /**
     * Paginate records for scaffold.
     */
    public function paginate(?int $perPage, array $columns = ['*']): LengthAwarePaginator
    {
        if (empty($perPage)) {
            return $this->baseQuery->paginate($this->baseQuery->count());
        }
        return $this->baseQuery->paginate($perPage, $columns);
    }

    protected function verifyLimiter(): void
    {
        if ($this->model->hasCompanyId()) {
            $this->baseQuery->where('company_id', auth('api')->user()->company_id ?? 1);
        }
    }

    /**
     * Retorna as iniciais de um nome/frase informado
     * @param  string  $value
     * @return string
     */
    protected function initials(string $value): string
    {
        $words = explode(' ', $value);
        $initials = null;
        foreach ($words as $word) {
            $initials .= $word[0];
        }
        return strtoupper($initials);
    }
}
