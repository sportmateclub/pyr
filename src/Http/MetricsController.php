<?php

declare(strict_types = 1);

namespace Beat\Pyr\Http;

use Beat\Pyr\PrometheusExporter;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Routing\Controller;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;

class MetricsController extends Controller
{
    protected ResponseFactory $responseFactory;
    protected PrometheusExporter $prometheusExporter;

    /**
     * @param ResponseFactory    $responseFactory
     * @param PrometheusExporter $prometheusExporter
     */
    public function __construct(ResponseFactory $responseFactory, PrometheusExporter $prometheusExporter)
    {
        $this->responseFactory = $responseFactory;
        $this->prometheusExporter = $prometheusExporter;

        $this->middleware(IpWhitelistMiddleware::class);
    }

    /**
     * GET /metrics
     *
     * The route path is configurable in the pyr.metrics_route_path config var, or the
     * PYR_METRICS_ROUTE_PATH env var.
     *
     * @return Response
     */
    public function getMetrics() : Response
    {
        $metrics = $this->prometheusExporter->export();

        $renderer = new RenderTextFormat();
        $result = $renderer->render($metrics);

        return $this->responseFactory->make($result, 200, ['Content-Type' => RenderTextFormat::MIME_TYPE]);
    }
}
