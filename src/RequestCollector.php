<?php

namespace Beat\Pyr;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;

class RequestCollector implements CollectorInterface
{
    public function getName(): string
    {
        return 'requests';
    }

    public function registerMetrics(PrometheusExporter $exporter): void
    {
        $histogram = $exporter->getOrRegisterHistogram(
            'response_time_seconds',
            'It observes response time.',
            [
                'method',
                'route',
                'status_code',
            ],
            [10, 25, 50, 100, 150, 200, 350, 500, 750, 1000, 1500, 2000, 3000, 5000]
        );

        Event::listen(RequestHandled::class, function (RequestHandled $event) use ($histogram) {
            if ($event->request->is($this->ignoredPaths())) {
                return;
            }

            $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');
            $duration = $startTime ? floor((microtime(true) - $startTime) * 1000) : null;

            $histogram->observe(
                $duration,
                [
                    $event->request->method(),
                    $event->request->getPathInfo(),
                    $event->response->getStatusCode(),
//                    round(memory_get_peak_usage(true) / 1024 / 1024, 1)
                ]
            );
        });
    }

    public function collect(): void
    {

    }

    protected function ignoredPaths()
    {
        return array_merge([
            config('telescope.path').'*',
            'telescope-api*',
            'vendor/telescope*',
            'horizon*',
            'vendor/horizon*',
            '_tt*',
            config('prometheus.metrics_route_path').'*',
        ], config('telescope.ignore_paths', []));
    }
}
