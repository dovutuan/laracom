<?php

namespace Dovutuan\Laracom\CacheRepository\Traits;

use Illuminate\Support\Facades\Cache;

trait CacheableRepository
{

    /**
     * function get key cache
     * @param string|int|null $id
     * @param array|null $conditions
     * @param array|string|null $relationships
     * @param array|string|null $count_relationships
     * @return string|null
     */
    protected function getKeyCache(
        string|int|null   $id,
        array|null        $conditions,
        array|string|null $relationships = null,
        array|string|null $count_relationships = null): ?string
    {
        $key = null;
        $model = $this->model->getTable();

        if ($id) {
            $key = "{$model}_$id";
        } elseif ($conditions) {
            ksort($conditions, SORT_STRING);
            $result = implode(
                '_',
                array_map(fn($value, $key) => ($key . '_' . $value), $conditions, array_keys($conditions))
            );

            $key = "{$model}_$result";
        }

        if ($relationships) {
            $key .= '_relationship_'
                . (is_array($relationships) ? implode('_', $relationships) : $relationships);
        }
        if ($count_relationships) {
            $key .= '_count_relationships_'
                . (is_array($count_relationships) ? implode('_', $count_relationships) : $count_relationships);
        }

        return $key;
    }

    /**
     * function get data cache
     * @param string $keyCache
     * @return mixed
     */
    protected function getCache(string $keyCache): mixed
    {
        return Cache::get($keyCache);
    }

    protected function setCache($key, $data)
    {
        Cache::put($key, $data);
    }

    protected function makeKeyCache()
    {
    }

    protected function isCache()
    {

    }
}