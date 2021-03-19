<?php

namespace Beat\Pyr;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

class GuzzleCollector implements CollectorInterface
{
    public function getName(): string
    {
        return 'external_requests';
    }

    public function registerMetrics(PrometheusExporter $exporter): void
    {
        $histogram = $exporter->getOrRegisterHistogram(
            'guzzle_response_duration',
            'Guzzle response duration histogram',
            ['method', 'external_endpoint', 'status_code', 'path']
        );

        $stack = HandlerStack::create(new CurlHandler());
        $stack->push(new GuzzleMiddleware($histogram));

        // @TODO: how to expose this guzzle client?
//        return new Client(['handler' => $app['pyr.guzzle.handler-stack']]);
    }

    public function collect(): void
    {

    }
}
