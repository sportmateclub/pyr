<?php

namespace Beat\Pyr;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

class QueryCollector implements CollectorInterface
{
    public function getName(): string
    {
        return 'queries';
    }

    public function registerMetrics(PrometheusExporter $exporter): void
    {
        $histogram = $exporter->getOrRegisterHistogram(
            'mysql_query_duration',
            'MySQL query duration histogram',
            array_values(array_filter([
                config('prometheus.collect_full_sql_query') ? 'query' : null,
                'query_type'
            ])),
            [0.1, 0.5, 0.75, 1, 2, 5, 10, 25, 50, 100, 150, 200, 500, 1000],
        );

        DB::listen(function (QueryExecuted $query) use ($histogram) {
            $type = strtoupper(strtok($query->sql, ' '));
            $labels = array_values(array_filter([
                config('prometheus.collect_full_sql_query') ? $query->sql : null,
                $type
            ]));
            $histogram->observe($query->time, $labels);
        });
    }

    public function collect(): void
    {

    }
}
