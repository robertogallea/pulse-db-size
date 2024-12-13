<?php

namespace Robertogallea\PulseDBSize\Recorders;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Exceptions\InvalidArgumentException;
use Laravel\Pulse\Events\IsolatedBeat;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\Concerns\Throttling;

class DBSizeRecorder
{
    use Throttling;

    public string $listen = IsolatedBeat::class;

    public function __construct(protected Pulse $pulse, protected Repository $config) {}

    public function record(IsolatedBeat $event)
    {
        $throttle = $this->config->get('pulse.recorders.'.self::class.'.throttle', 15);
        $this->throttle($throttle, $event, function ($event) {
            $connection = $this->config->get('pulse.recorders.'.self::class.'.connection') ??
                $this->config->get('database.default');

            $driver = $this->config->get('database.connections.'.$connection.'.driver');

            $ignoredTables = collect($this->config->get('pulse.recorders.'.self::class.'.ignore', []))
                ->map(function (string $table) {
                    return "'$table'";
                })
                ->implode(',');
            
            $onlyTables = collect($this->config->get('pulse.recorders.'.self::class.'.only', []))
                ->map(function (string $table) {
                    return "'$table'";
                })
                ->implode(',');

            $operator = null;

            // "only" is prioritized because of like "least-selection" principle
            if (!empty($onlyTables)) {
                $operator = 'IN'
            } else if (!empty($ignoredTables)) {
                $operator = 'NOT IN'
            }
    
            # If both are empty then operator is null and WHERE won't get added anyways
            $tables = !empty($onlyTables) ? $onlyTables : $ignoredTables;

            $tableSizes = collect(DB::select(match ($driver) {
                'sqlite' => $this->getSqliteQuery($tables, $operator),
                'mysql', 'mariadb' => $this->getMySQLMariaDBQuery($connection, $tables, $operator),
                'pgsql' => $this->getPgSQLQuery($tables, $operator),
                'oracle' => $this->getOracleQuery($tables, $operator),
                default => throw new InvalidArgumentException("Driver $driver is not supported.")
            }))
                ->mapWithKeys(function ($item) {
                    return [$item->name => $item->size];
                });

            $this->pulse->set('db-size', 'tables', json_encode($tableSizes), $event->time);
        });
    }

    public function getSqliteQuery(string $tables, string $operator): string
    {
        return "SELECT SUM(pgsize) as size, name FROM 'dbstat'" .
                    (!$operator ? '' : 'WHERE name ' . $operator . ' (' . $tables . ') ') .
                    'group by name;';
    }

    public function getMySQLMariaDBQuery(mixed $connection, string $tables, string $operator): string
    {
        return 'SELECT table_name AS name, (data_length + index_length) AS size
                    FROM information_schema.TABLES
                    WHERE table_schema = "' . $this->config->get('database.connections.' . $connection . '.database') . '"'.
                    (!$operator ? '' : ' AND table_name ' . $operator . ' (' . $tables . ')');
    }

    public function getPgSQLQuery(string $tables, string $operator): string
    {
        return 'SELECT
                   relname as name,
                   pg_relation_size(relid) As size
                   FROM pg_catalog.pg_statio_user_tables' .
                   (!$operator ? '' : 'WHERE relname ' . $operator . ' (' . $tables . ') ') .
                   'ORDER BY pg_total_relation_size(relid) DESC';
    }

    public function getOracleQuery(string $tables, string $operator): string
    {
        return 'SELECT bytes "size", segment_name "name" 
                    FROM user_segments 
                    WHERE segment_type = \'TABLE\' '.
                    (!$operator ? '' : 'AND segment_name ' . $operator . ' (' . $tables . ')');
    }
}
