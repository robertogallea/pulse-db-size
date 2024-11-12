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
            $connection = $this->config->get('pulse.recorders.'.self::class.'.connection') ?? $this->config->get('database.default');

            $driver = $this->config->get('database.connections.'.$connection.'.driver');

            $tableSizes = collect(match ($driver) {
                'sqlite' => DB::select("SELECT SUM(pgsize) as size, name FROM 'dbstat'  group by name;"),
                'mysql', 'mariadb' => DB::select('SELECT table_name AS name,
                    (data_length + index_length) AS size
                    FROM information_schema.TABLES
                    WHERE table_schema = "'.$this->config->get('database.connections.'.$connection.'.database').'"
                    ORDER BY (data_length + index_length) DESC;'),
                'pgsql' => DB::select('SELECT
                   relname as name,
                   pg_relation_size(relid) As size
                   FROM pg_catalog.pg_statio_user_tables ORDER BY pg_total_relation_size(relid) DESC;'),
                default => throw new InvalidArgumentException("Driver $driver is not supported.")
            })
                ->mapWithKeys(function ($item) {
                    return [$item->name => $item->size];
                });

            $this->pulse->set('db-size', 'tables', json_encode($tableSizes), $event->time);
        });
    }
}
