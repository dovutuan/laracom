<?php

namespace Dovutuan\Laracom\DomRepository\Traits;

use Dovutuan\Laracom\DomRepository\Events\AfterCreateEvent;
use Dovutuan\Laracom\DomRepository\Events\AfterDeleteEvent;
use Dovutuan\Laracom\DomRepository\Events\AfterUpdateEvent;
use Dovutuan\Laracom\DomRepository\Events\BeforeCreateEvent;
use Dovutuan\Laracom\DomRepository\Events\BeforeDeleteEvent;
use Dovutuan\Laracom\DomRepository\Events\BeforeUpdateEvent;
use Dovutuan\Laracom\DomRepository\Exception\NotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait BuildsQueries
{
    /**
     * @param array|null $conditions
     * @return Builder
     */
    private function buildQuery(array|null $conditions = null): Builder
    {
        $query = $this->model->newQuery();

        if ($conditions && count($conditions)) {
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
                                $operator = !isset($item['operator']) ? OPERATOR_EQUAL : $item['operator'];
                                $column = $item['column'];

                                switch ($operator) {
                                    case OPERATOR_EQUAL:
                                        $qr->where($column, '=', $value, $boolean);
                                        break;
                                    case OPERATOR_NOT_EQUAL:
                                        $qr->where($column, '<>', $value);
                                        break;
                                    case OPERATOR_LIKE:
                                        $qr->where($column, 'like', "%$value%", $boolean);
                                        break;
                                    case OPERATOR_BEFORE_LIKE:
                                        $qr->where($column, 'like', "%$value", $boolean);
                                        break;
                                    case OPERATOR_AFTER_LIKE:
                                        $qr->where($column, 'like', "$value%", $boolean);
                                        break;
                                    case OPERATOR_NOT_LIKE:
                                        $qr->where($column, 'not like', "$value%", $boolean);
                                        break;
                                    case OPERATOR_GREATER:
                                        $qr->where($column, '>', $value, $boolean);
                                        break;
                                    case OPERATOR_GREATER_EQUAL:
                                        $qr->where($column, '>=', $value, $boolean);
                                        break;
                                    case OPERATOR_LESS:
                                        $qr->where($column, '<', $value, $boolean);
                                        break;
                                    case OPERATOR_LES_EQUAL:
                                        $qr->where($column, '<=', $value, $boolean);
                                        break;
                                    case OPERATOR_IN:
                                        $qr->whereIn($column, $value, $boolean);
                                        break;
                                    case OPERATOR_NOT_IN:
                                        $qr->whereNotIn($column, $value, $boolean);
                                        break;
                                    case OPERATOR_NULL:
                                        $qr->whereNull($column, $value, $boolean);
                                        break;
                                    case OPERATOR_NOT_NULL:
                                        $qr->whereNotNull($column, $value);
                                        break;
                                    case OPERATOR_DATE:
                                        $qr->whereDate($column, '=', $value, $boolean);
                                        break;
                                    case OPERATOR_DATE_NOT_EQUAL:
                                        $qr->whereDate($column, '<>', $value, $boolean);
                                        break;
                                    case OPERATOR_DATE_GREATER:
                                        $qr->whereDate($column, '>', $value, $boolean);
                                        break;
                                    case OPERATOR_DATE_GREATER_EQUAL:
                                        $qr->whereDate($column . '>=', $value, $boolean);
                                        break;
                                    case OPERATOR_DATE_LESS:
                                        $qr->whereDate($column, '<', $value, $boolean);
                                        break;
                                    case OPERATOR_DATE_LESS_EQUAL:
                                        $qr->whereDate($column, '<=', $value, $boolean);
                                        break;
                                    case OPERATOR_JSON:
                                        $qr->whereJsonContains($column, $value, $boolean);
                                        break;
                                    case OPERATOR_JSON_NOT_CONTAIN:
                                        $qr->whereJsonDoesntContain($column, $value, $boolean);
                                        break;
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
     * @param Model|Builder $query
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @return void
     */
    private function buildRelationship(
        Model|Builder     $query,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null): void
    {
        if ($relationships) {
            ($query instanceof Model) ? $query->load($relationships) : $query->with($relationships);
        }
        if ($count_relationships) {
            ($query instanceof Model) ? $query->loadCount($relationships) : $query->withCount($relationships);
        }
    }

    /**
     * @param Builder $query
     * @param string|null $order_by
     * @return void
     */
    private function buildOrderBy(Builder $query, string|null $order_by = null): void
    {
        if ($order_by) {
            $method = 'customOrderBys';
            if (method_exists($this, $method)) {
                $this->{$method}($query, $order_by);
            } else {
                $order_bys = explode('|', $order_by);
                if ($order_bys && count($order_bys)) {
                    foreach ($order_bys as $order) {
                        [$column, $direction] = explode('-', $order);
                        $method = 'customOrderBy' . Str::studly($column);
                        method_exists($this, $method)
                            ? $this->{$method}($query, $column, $direction)
                            : $query->orderBy($column, $direction);
                    }
                }
            }
        }
    }

    private function buildGroupBy(Builder $query, string|null $group_by = null): void
    {
        if ($group_by) {
            $method = 'customGroupBys';
            if (method_exists($this, $method)) {
                $this->{$method}($query, $group_by);
            } else {
                $group_bys = explode('|', $group_by);
                if ($group_bys && count($group_bys)) {
                    foreach ($group_bys as $column) {
                        $method = 'customGroupBy' . Str::studly($column);
                        method_exists($this, $method)
                            ? $this->{$method}($query, $column)
                            : $query->groupBy($column);
                    }
                }
            }
        }
    }

    /**
     * @param string|int|null $id
     * @param array|null $conditions
     * @param string|array $columns
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param bool $throw_exception
     * @return Model|Builder|null
     * @throws NotFoundException
     */
    private function findByIdOrConditions(
        string|int|null   $id = null,
        array|null        $conditions = null,
        string|array      $columns = '*',
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        bool              $throw_exception = true): Model|Builder|null
    {
        $model = $id ? $this->model->newQuery()->find($id, $columns) : $this->buildQuery($conditions)->first($columns);
        if ($model) {
            $this->buildRelationship($model, $relationships, $count_relationships);

            return $model;
        }

        $throw_exception && (throw new NotFoundException($this->model->getTable() . '_not_found'));

        return null;
    }


    /**
     * @param array $data
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @return Model|Builder
     */
    private function create(
        array             $data,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null): Model|Builder
    {
        event(new BeforeCreateEvent($data));

        $model = $this->model->newQuery()->create($data);
        $this->buildRelationship($model, $relationships, $count_relationships);

        event(new AfterCreateEvent($model->toArray()));

        return $model;
    }

    /**
     * @param array $data
     * @param int|string|null $id
     * @param array|null $conditions
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @param bool $throw_exception
     * @return Model|Builder|null
     * @throws NotFoundException
     */
    private function updateByIdOrConditions(
        array             $data,
        int|string|null   $id = null,
        array|null        $conditions = null,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null,
        bool              $throw_exception = true): Model|Builder|null
    {
        $model = $id ? $this->model->newQuery()->find($id) : $this->buildQuery($conditions)->first();
        if ($model) {

            event(new BeforeUpdateEvent($data));

            $model->update($data);
            $this->buildRelationship($model, $relationships, $count_relationships);

            event(new AfterUpdateEvent($data, $model->toArray()));

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
        int|string|null $id = null,
        array|null      $conditions = null,
        bool            $is_delete_multi = false,
        bool            $throw_exception = true): null
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

            event(new BeforeDeleteEvent($model->toArray()));

            $model->delete();

            event(new AfterDeleteEvent());
        }

        $throw_exception && (throw new NotFoundException($this->model->getTable() . '_not_found'));

        return null;
    }
}
