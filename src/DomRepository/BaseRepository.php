<?php

namespace Dovutuan\Laracom\DomRepository;

use Dovutuan\Laracom\DomRepository\Exception\NotFoundException;
use Illuminate\Container\Container as Application;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseRepository
{
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
     * @param array $conditions
     * @return Builder
     */
    private function buildQuery(array $conditions): Builder
    {
        $query = $this->model->newQuery();

        if (count($conditions)) {
            foreach ($conditions as $key => $value) {
                $method = 'query' . Str::studly($key);

                if (method_exists($this, $method)) {
                    $this->{$method}($query, $key, $value);
                } else {
                    $field_search = $this->base_search[$key] ?? null;
                    if ($field_search) {
                        $query->where(function (Builder $qr) use ($field_search, $value) {
                            foreach ($field_search as $item) {
                                $boolean = $item['boolean'] ?? 'and';
                                if (!isset($item['operator']) || $item['operator'] === OPERATOR_EQUAL) {
                                    $qr->where($item['column'], '=', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_NOT_EQUAL) {
                                    $qr->where($item['column'], '<>', $value);
                                } elseif ($item['operator'] === OPERATOR_LIKE) {
                                    $qr->where($item['column'], 'like', "%$value%", $boolean);
                                } elseif ($item['operator'] === OPERATOR_BEFORE_LIKE) {
                                    $qr->where($item['column'], 'like', "%$value", $boolean);
                                } elseif ($item['operator'] === OPERATOR_AFTER_LIKE) {
                                    $qr->where($item['column'], 'like', "$value%", $boolean);
                                } elseif ($item['operator'] === OPERATOR_NOT_LIKE) {
                                    $qr->where($item['column'], 'not like', "$value%", $boolean);
                                } elseif ($item['operator'] === OPERATOR_GREATER) {
                                    $qr->where($item['column'], '>', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_GREATER_EQUAL) {
                                    $qr->where($item['column'], '>=', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_LESS) {
                                    $qr->where($item['column'], '<', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_LES_EQUAL) {
                                    $qr->where($item['column'], '<=', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_IN) {
                                    $qr->whereIn($item['column'], $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_NOT_IN) {
                                    $qr->whereNotIn($item['column'], $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_NULL) {
                                    $qr->whereNull($item['column'], $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_NOT_NULL) {
                                    $qr->whereNotNull($item['column'], $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_DATE) {
                                    $qr->whereDate($item['column'], '=', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_DATE_NOT_EQUAL) {
                                    $qr->whereDate($item['column'], '<>', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_DATE_GREATER) {
                                    $qr->whereDate($item['column'], '>', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_DATE_GREATER_EQUAL) {
                                    $qr->whereDate($item['column'] . '>=', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_DATE_LESS) {
                                    $qr->whereDate($item['column'], '<', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_DATE_LESS_EQUAL) {
                                    $qr->whereDate($item['column'], '<=', $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_JSON) {
                                    $qr->whereJsonContains($item['column'], $value, $boolean);
                                } elseif ($item['operator'] === OPERATOR_JSON_NOT_CONTAIN) {
                                    $qr->whereJsonDoesntContain($item['column'], $value, $boolean);
                                }
                            }
                        });
                    }
                }
            }
        }

        return $query;
    }

    /**
     * @param string|int|null $id
     * @param array|null $conditions
     * @param string|array $columns
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param bool $throw_exception
     * @return Model|Builder|array|null
     * @throws NotFoundException
     */
    private function findByIdOrConditions(
        string|int|null   $id,
        array|null        $conditions,
        string|array      $columns = '*',
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        bool              $throw_exception = true): Model|Builder|array|null
    {
        $model = $id ? $this->model->newQuery()->find($id, $columns) : $this->buildQuery($conditions)->first($columns);
        if ($model) {
            $relationships && $model->load($relationships);
            $count_relationships && $model->loadCount($count_relationships);

            return $model;
        }

        $throw_exception && (throw new NotFoundException($this->model->getTable() . '_not_found'));

        return null;
    }

    /**
     * @param int|string|null $id
     * @param array|null $conditions
     * @param array $data
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param bool $throw_exception
     * @return Model|Builder|null
     * @throws NotFoundException
     */
    private function updateByIdOrConditions(
        int|string|null   $id,
        array|null        $conditions,
        array             $data,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        bool              $throw_exception = true): Model|Builder|null
    {
        $model = $id ? $this->model->newQuery()->find($id) : $this->buildQuery($conditions)->first();
        if ($model) {
            $model->update($data);
            if ($relationships) $model->load($relationships);
            if ($count_relationships) $model->loadCount($count_relationships);

            return $model;
        }

        $throw_exception && (throw new NotFoundException($this->model->getTable() . '_not_found'));

        return null;
    }

    /**
     * @param int|string|null $id
     * @param array|null $conditions
     * @param bool $throw_exception
     * @param bool $is_delete_multi
     * @return null
     * @throws NotFoundException
     */
    private function deleteByIdOrConditions(
        int|string|null $id,
        array|null      $conditions,
        bool            $is_delete_multi = false,
        bool            $throw_exception = true,
    )
    {
        $model = null;
        if ($id) {
            $model = $this->model->newQuery()->find($id);
        } elseif (!$is_delete_multi && $conditions) {
            $model = $this->buildQuery($conditions)->first();
        } elseif ($is_delete_multi && $conditions) {
            $model = $this->buildQuery($conditions)->get();
        }

        if ((!$is_delete_multi && $model) || ($is_delete_multi && $model->count())) {
            $model->delete();
        }

        $throw_exception && (throw new NotFoundException($this->model->getTable() . '_not_found'));

        return null;
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
        bool              $throw_exception = true): Model|Collection|Builder|array|null
    {
        return $this->findByIdOrConditions(
            $id,
            null,
            $columns,
            $relationships,
            $count_relationships,
            $throw_exception);
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
            null,
            $conditions,
            $columns,
            $relationships,
            $count_relationships,
            $throw_exception);
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
        if ($relationships) $model->load($relationships);
        if ($count_relationships) $model->loadCount($count_relationships);

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
            $id,
            null,
            $data,
            $relationships,
            $count_relationships,
            $throw_exception);
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
            null,
            $conditions,
            $data,
            $relationships,
            $count_relationships,
            $throw_exception);
    }

    /**
     * @param string|int $id
     * @param bool $throw_exception
     * @return null
     * @throws NotFoundException
     */
    public function delete(
        string|int $id,
        bool       $throw_exception = true)
    {
        return $this->deleteByIdOrConditions($id, null, false, $throw_exception);
    }

    /**
     * @param array $conditions
     * @param bool $throw_exception
     * @return null
     * @throws NotFoundException
     */
    public function deleteByConditions(
        array $conditions,
        bool  $throw_exception = true)
    {
        return $this->deleteByIdOrConditions(null, $conditions, false, $throw_exception);
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
    )
    {
        return $this->deleteByIdOrConditions(null, $conditions, true, $throw_exception);
    }
}
