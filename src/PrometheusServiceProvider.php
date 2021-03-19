<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use Beat\Pyr\Http\MetricsController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot() : void
    {
        if (!config('pyr.enabled', false)) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pyr.php' => $this->configPath('pyr.php'),
            ], 'pyr-config');
        }

        $this->loadRoutes();

        /* @var PrometheusExporter $exporter */
        $exporter = $this->app->make(PrometheusExporter::class);

        foreach (config('pyr.collectors', []) as $class) {
            $collector = $this->app->make($class);
            $exporter->registerCollector($collector);
        }
    }

    /**
     * Register bindings in the container.
     */
    public function register() : void
    {
        if (!config('pyr.enabled', false)) {
            return;
        }

        $this->mergeConfigFrom(__DIR__ . '/../config/pyr.php', 'pyr');

        $this->app->singleton(PrometheusExporter::class, function ($app) {
            $adapter = $app['pyr.storage_adapter'];
            $prometheus = new CollectorRegistry($adapter);

            return new PrometheusExporter(config('pyr.namespace'), $prometheus);
        });
        $this->app->alias(PrometheusExporter::class, 'pyr');

        $this->app->bind('pyr.storage_adapter_factory', function () {
            return new StorageAdapterFactory();
        });

        $this->app->bind(Adapter::class, function ($app) {
            /* @var StorageAdapterFactory $factory */
            $factory = $app['pyr.storage_adapter_factory'];
            $driver = config('pyr.storage_adapter');
            $configs = config('pyr.storage_adapters');
            $config = Arr::get($configs, $driver, []);

            return $factory->make($driver, $config);
        });
        $this->app->alias(Adapter::class, 'pyr.storage_adapter');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() : array
    {
        return [
            'pyr',
            'pyr.storage_adapter',
            'pyr.storage_adapter_factory',
        ];
    }

    private function loadRoutes()
    {
        if (!config('pyr.metrics_route_enabled')) {
            return;
        }

        $router = $this->app['router'];

        /** @var Route $route */
        $router->get(
            config('pyr.metrics_route_path'),
            MetricsController::class . '@getMetrics'
        )->name('metrics');
    }

    private function configPath($path) : string
    {
        return $this->app->basePath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
