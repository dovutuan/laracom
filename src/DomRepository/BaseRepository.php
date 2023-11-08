<?php

namespace Dovutuan\Laracom\DomRepository;

use Dovutuan\Laracom\DomRepository\Exception\NotFoundException;
use Dovutuan\Laracom\DomRepository\Interface\RepositoryInterface;
use Dovutuan\Laracom\DomRepository\Traits\BuildsQueries;
use Illuminate\Container\Container as Application;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as CollectionAlias;

abstract class BaseRepository implements RepositoryInterface
{
    use BuildsQueries;

    private Application $app;
    private ConfigRepository $config;
    protected Model $model;

    abstract public function model();

    protected array $base_search = [];

    /**
     * @param Application $app
     * @param ConfigRepository $config
     * @throws BindingResolutionException
     */
    public function __construct(Application $app, ConfigRepository $config)
    {
        $this->app = $app;
        $this->config = $config;
        $this->makeModel();
    }

    /**
     * function initialize model
     * @return mixed
     * @throws BindingResolutionException
     */
    public function makeModel(): mixed
    {
        $model = $this->app->make($this->model());

        return $this->model = $model;
    }

    /**
     * @param string|int $id
     * @param string|array $columns
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param bool $throw_exception
     * @return Model|Collection|Builder|array|null
     * @throws NotFoundException
     */
    public function find(
        string|int        $id,
        string|array      $columns = '*',
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        bool              $throw_exception = true): Model|Collection|Builder|null
    {
        return $this->findByIdOrConditions(
            id: $id,
            columns: $columns,
            relationships: $relationships,
            count_relationships: $count_relationships,
            throw_exception: $throw_exception);
    }

    /**
     * @param array $conditions
     * @param string|array $columns
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param bool $throw_exception
     * @return Model|Builder|null
     * @throws NotFoundException
     */
    public function findByConditions(
        array             $conditions,
        string|array      $columns = '*',
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        bool              $throw_exception = true): Model|Builder|null
    {
        return $this->findByIdOrConditions(
            conditions: $conditions,
            columns: $columns,
            relationships: $relationships,
            count_relationships: $count_relationships,
            throw_exception: $throw_exception);
    }

    /**
     * @param array $data
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @return Builder|Model
     */
    public function create(
        array             $data,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null): Model|Builder
    {
        $model = $this->model->newQuery()->create($data);
        $this->buildRelationship($model, $relationships, $count_relationships);

        return $model;
    }

    /**
     * @param int|string $id
     * @param array $data
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param bool $throw_exception
     * @return Model|Builder|null
     * @throws NotFoundException
     */
    public function update(
        int|string        $id,
        array             $data,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        bool              $throw_exception = true): Model|Builder|null
    {
        return $this->updateByIdOrConditions(
            data: $data,
            id: $id,
            relationships: $relationships,
            count_relationships: $count_relationships,
            throw_exception: $throw_exception);
    }

    /**
     * @param array $conditions
     * @param array $data
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param bool $throw_exception
     * @return Builder|Model|null
     * @throws NotFoundException
     */
    public function updateByConditions(
        array             $conditions,
        array             $data,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        bool              $throw_exception = true): Model|Builder|null
    {
        return $this->updateByIdOrConditions(
            data: $data,
            conditions: $conditions,
            relationships: $relationships,
            count_relationships: $count_relationships,
            throw_exception: $throw_exception);
    }

    /**
     * @param string|int $id
     * @param bool $throw_exception
     * @return null
     * @throws NotFoundException
     */
    public function delete(
        string|int $id,
        bool       $throw_exception = true): null
    {
        return $this->deleteByIdOrConditions(
            id: $id,
            throw_exception: $throw_exception);
    }

    /**
     * @param array $conditions
     * @param bool $throw_exception
     * @return null
     * @throws NotFoundException
     */
    public function deleteByConditions(
        array $conditions,
        bool  $throw_exception = true): null
    {
        return $this->deleteByIdOrConditions(
            conditions: $conditions,
            throw_exception: $throw_exception);
    }

    /**
     * @param array $conditions
     * @param bool $throw_exception
     * @return null
     * @throws NotFoundException
     */
    public function deletesByConditions(
        array $conditions,
        bool  $throw_exception = true
    ): null
    {
        return $this->deleteByIdOrConditions(
            conditions: $conditions,
            is_delete_multi: true,
            throw_exception: $throw_exception);
    }

    /**
     * @param array|null $conditions
     * @return int
     */
    public function count(array|null $conditions = null): int
    {
        $query = $this->buildQuery($conditions);

        return $query->count();
    }

    /**
     * @param array|null $conditions
     * @param int $page
     * @param int $limit
     * @param string|null $order_by
     * @param string|null $group_by
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param array $columns
     * @return array
     */
    public function paginate(
        array|null        $conditions = null,
        int               $page = 0,
        int               $limit = 10,
        string            $order_by = null,
        string            $group_by = null,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        array             $columns = ['*']): array
    {
        $total = $this->count($conditions);
        $results = null;
        if ($total) {
            $query = $this->buildQuery($conditions);
            $this->buildOrderBy($query, $order_by);
            $this->buildGroupBy($query, $group_by);
            $this->buildRelationship($query, $relationships, $count_relationships);

            $results = $query
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get($columns);
        }

        return compact('total', 'results');
    }

    /**
     * @param array|null $conditions
     * @param string|null $order_by
     * @param string|null $group_by
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param array $columns
     * @return Collection|array
     */
    public function all(
        array|null        $conditions = null,
        string            $order_by = null,
        string            $group_by = null,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        array             $columns = ['*']): Collection|array
    {
        $query = $this->buildQuery($conditions);
        $this->buildOrderBy($query, $order_by);
        $this->buildGroupBy($query, $group_by);
        $this->buildRelationship($query, $relationships, $count_relationships);

        return $query->get($columns);
    }

    /**
     * @param array $data
     * @return true
     */
    public function insert(array $data): true
    {
        $this->model->newQuery()->insert($data);

        return true;
    }

    /**
     * @param array $conditions
     * @param array $data
     * @return Model
     */
    public function updateOrCreate(
        array $conditions,
        array $data): Model
    {
        $model = $this->buildQuery($conditions)->first();
        if ($model) {
            $model->update($data);
        } else {
            $model = $this->create($data);
        }
        return $model;
    }

    /**
     * @param array $data
     * @param array $keys
     * @param array $columns
     * @return true
     */
    public function upsert(
        array $data,
        array $keys,
        array $columns): true
    {
        $this->model->newQuery()->upsert($data, $keys, $columns);

        return true;
    }

    /**
     * @param string $column
     * @param string|null $key
     * @param array|null $conditions
     * @return CollectionAlias
     */
    public function allAndPluck(
        string      $column,
        string|null $key = null,
        array|null  $conditions = null): CollectionAlias
    {
        $query = $this->buildQuery($conditions);

        return $query->pluck($column, $key);
    }
}
