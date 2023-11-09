<?php

namespace Dovutuan\Laracom;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class LogQueryProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->logQuery();
    }

    /**
     * @return void
     */
    private function logQuery(): void
    {
        if (config('laracom.log_query')) {
            $now = date('Y-m-d');
            File::delete("storage/app/query/sql-$now.sql");

            $maxSize = 2000000; // ~2Mb
            $nameFix = 'query/sql-' . date('Y-m-d');
            $name = "{$nameFix}.sql";
            $index = 0;
            while (
                Storage::disk('local')->exists($name)
                && Storage::disk('local')->size($name) >= $maxSize
            ) {
                $index += 1;
                $name = "{$nameFix}-{$index}.sql";
            }
            Storage::disk('local')->append($name, "----------START---------");

            DB::listen(function ($query) use ($name) {
                $binding = $query->bindings;
                $binding = array_map(function ($bd) {
                    if (is_object($bd)) {
                        return "'{$bd->format('Y-m-d H:i:s')}'";
                    } else {
                        return "'$bd'";
                    }
                }, $binding);

                $boundSql = str_replace(['%', '?'], ['%%', '%s'], $query->sql);
                $boundSql = vsprintf($boundSql, $binding);

                $sql = "Date: " . date('Y-m-d H:i:s') . "\n";
                $sql .= "Time query: $query->time(ms)\n";
                $sql .= "$boundSql;\n";
                $sql .= "----------END----------\n";

                Storage::disk('local')->append($name, $sql);
            });
        }
    }
}
