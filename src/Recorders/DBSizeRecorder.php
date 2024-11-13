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

            $tableSizes = collect(DB::select(match ($driver) {
                'sqlite' => $this->getSqliteQuery($ignoredTables),
                'mysql', 'mariadb' => $this->getMySQLMariaDBQuery($connection, $ignoredTables),
                'pgsql' => $this->getPgSQLQuery($ignoredTables),
                'oracle' => $this->getOracleQuery($ignoredTables),
                default => throw new InvalidArgumentException("Driver $driver is not supported.")
            }))
                ->mapWithKeys(function ($item) {
                    return [$item->name => $item->size];
                });

            $this->pulse->set('db-size', 'tables', json_encode($tableSizes), $event->time);
        });
    }

    public function getSqliteQuery(string $ignoredTables): string
    {
        return "SELECT SUM(pgsize) as size, name FROM 'dbstat'".
                    (empty($ignoredTables) ? '' : 'WHERE name NOT IN ('.$ignoredTables.') ').
                                 'group by name;';
    }

    public function getMySQLMariaDBQuery(mixed $connection, string $ignoredTables): string
    {
        return 'SELECT table_name AS name, (data_length + index_length) AS size
                    FROM information_schema.TABLES
                    WHERE table_schema = "'.$this->config->get('database.connections.'.$connection.'.database').'"'.
                    (empty($ignoredTables) ? '' : ' AND table_name NOT IN ('.$ignoredTables.')');
    }

    public function getPgSQLQuery(string $ignoredTables): string
    {
        return 'SELECT
                   relname as name,
                   pg_relation_size(relid) As size
                   FROM pg_catalog.pg_statio_user_tables 
                   WHERE '.(empty($ignoredTables) ? '' : 'relname NOT IN ('.$ignoredTables.') ').
                   'ORDER BY pg_total_relation_size(relid) DESC';
    }

    public function getOracleQuery(string $ignoredTables): string
    {
        return 'SELECT bytes "size", segment_name "name" 
                    FROM user_segments 
                    WHERE segment_type = \'TABLE\' '.
                    (empty($ignoredTables) ? '' : 'AND segment_name NOT IN ('.$ignoredTables.')');
    }
}
