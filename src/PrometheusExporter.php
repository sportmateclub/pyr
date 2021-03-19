<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use InvalidArgumentException;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;

class PrometheusExporter
{
    protected string $namespace;
    protected CollectorRegistry $prometheus;
    protected array $collectors = [];

    /**
     * @param string            $namespace
     * @param CollectorRegistry $prometheus
     * @param array             $collectors
     */
    public function __construct(string $namespace, CollectorRegistry $prometheus, array $collectors = [])
    {
        $this->namespace = $namespace;
        $this->prometheus = $prometheus;

        foreach ($collectors as $collector) {
            /* @var CollectorInterface $collector */
            $this->registerCollector($collector);
        }
    }

    /**
     * Return the metric namespace.
     *
     * @return string
     */
    public function getNamespace() : string
    {
        return $this->namespace;
    }

    /**
     * Return the CollectorRegistry.
     *
     * @return CollectorRegistry
     */
    public function getPrometheus() : CollectorRegistry
    {
        return $this->prometheus;
    }

    /**
     * Register a collector.
     *
     * @param CollectorInterface $collector
     */
    public function registerCollector(CollectorInterface $collector) : void
    {
        $name = $collector->getName();

        if (!isset($this->collectors[$name])) {
            $this->collectors[$name] = $collector;

            $collector->registerMetrics($this);
        }
    }

    /**
     * Return all collectors.
     *
     * @return array
     */
    public function getCollectors() : array
    {
        return $this->collectors;
    }

    /**
     * Return a collector by name.
     *
     * @param string $name
     *
     * @return CollectorInterface
     */
    public function getCollector(string $name) : CollectorInterface
    {
        if (!isset($this->collectors[$name])) {
            throw new InvalidArgumentException(sprintf('The collector "%s" is not registered.', $name));
        }

        return $this->collectors[$name];
    }

    /**
     * Register a counter.
     *
     * @param string $name
     * @param string $help
     * @param array $labels
     *
     * @return Counter
     *
     * @see https://prometheus.io/docs/concepts/metric_types/#counter
     * @throws MetricsRegistrationException
     */
    public function registerCounter(string $name, string $help, $labels = []) : Counter
    {
        return $this->prometheus->registerCounter($this->namespace, $name, $help, $labels);
    }

    /**
     * Return a counter.
     *
     * @param string $name
     *
     * @return Counter
     * @throws MetricNotFoundException
     */
    public function getCounter(string $name) : Counter
    {
        return $this->prometheus->getCounter($this->namespace, $name);
    }

    /**
     * Return or register a counter.
     *
     * @param string $name
     * @param string $help
     * @param array $labels
     *
     * @return Counter
     *
     * @throws MetricsRegistrationException
     * @see https://prometheus.io/docs/concepts/metric_types/#counter
     */
    public function getOrRegisterCounter(string $name, string $help, $labels = []) : Counter
    {
        return $this->prometheus->getOrRegisterCounter($this->namespace, $name, $help, $labels);
    }

    /**
     * Register a gauge.
     *
     * @param string $name
     * @param string $help
     * @param array $labels
     *
     * @return Gauge
     *
     * @throws MetricsRegistrationException
     * @see https://prometheus.io/docs/concepts/metric_types/#gauge
     */
    public function registerGauge(string $name, string $help, $labels = []) : Gauge
    {
        return $this->prometheus->registerGauge($this->namespace, $name, $help, $labels);
    }

    /**
     * Return a gauge.
     *
     * @param string $name
     *
     * @return Gauge
     * @throws MetricNotFoundException
     */
    public function getGauge(string $name) : Gauge
    {
        return $this->prometheus->getGauge($this->namespace, $name);
    }

    /**
     * Return or register a gauge.
     *
     * @param string $name
     * @param string $help
     * @param array $labels
     *
     * @return Gauge
     *
     * @throws MetricsRegistrationException
     * @see https://prometheus.io/docs/concepts/metric_types/#gauge
     */
    public function getOrRegisterGauge(string $name, string $help, $labels = []) : Gauge
    {
        return $this->prometheus->getOrRegisterGauge($this->namespace, $name, $help, $labels);
    }

    /**
     * Register a histogram.
     *
     * @param string $name
     * @param string $help
     * @param array $labels
     * @param null $buckets
     *
     * @return Histogram
     *
     * @throws MetricsRegistrationException
     * @see https://prometheus.io/docs/concepts/metric_types/#histogram
     */
    public function registerHistogram(string $name, string $help, $labels = [], $buckets = null) : Histogram
    {
        return $this->prometheus->registerHistogram($this->namespace, $name, $help, $labels, $buckets);
    }

    /**
     * Return a histogram.
     *
     * @param string $name
     *
     * @return Histogram
     * @throws MetricNotFoundException
     */
    public function getHistogram(string $name) : Histogram
    {
        return $this->prometheus->getHistogram($this->namespace, $name);
    }

    /**
     * Return or register a histogram.
     *
     * @param string $name
     * @param string $help
     * @param array $labels
     * @param null $buckets
     *
     * @return Histogram
     *
     * @throws MetricsRegistrationException
     * @see https://prometheus.io/docs/concepts/metric_types/#histogram
     */
    public function getOrRegisterHistogram(string $name, string $help, $labels = [], $buckets = null) : Histogram
    {
        return $this->prometheus->getOrRegisterHistogram($this->namespace, $name, $help, $labels, $buckets);
    }

    /**
     * Export the metrics from all collectors.
     *
     * @return MetricFamilySamples[]
     */
    public function export() : array
    {
        foreach ($this->collectors as $collector) {
            /* @var CollectorInterface $collector */
            $collector->collect();
        }

        return $this->prometheus->getMetricFamilySamples();
    }
}
