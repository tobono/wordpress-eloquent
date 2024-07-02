<?php

namespace Tobono\WordPressEloquent;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Tobono\WPDB\WPDBServiceProvider;

class Eloquent
{
    /**
     * @var Application
     */
    protected $container;

    protected array $providers = [
        DatabaseServiceProvider::class,
        WPDBServiceProvider::class,
    ];

    private function __construct(protected array $config = [])
    {
        $this->container = new Container;

        $this->setupContainer();

        $this->runProviders();
    }

    public static function register(array $config = []): static
    {
        return new static($config);
    }

    protected function setupContainer(): void
    {
        $this->container->bindIf('events', fn() => new Dispatcher);

        $this->container->bindIf('config', fn() => [
            'database.default' => $this->config['default'] ?? 'wpdb',
            'database.connections' => $this->config['connections'] ?? [
                    'wpdb' => [
                        'driver' => 'wpdb',
                        'host' => DB_HOST,
                        'database' => DB_NAME,
                        'username' => DB_USER,
                        'password' => DB_PASSWORD,
                        'prefix' => $GLOBALS['wpdb']->prefix,
                    ],
                ],
        ]);
    }

    protected function runProviders(): void
    {
        collect($this->providers)
            ->map(fn(string $provider) => new $provider($this->container))
            ->each(function (ServiceProvider $provider) {
                $provider->register();

                if (method_exists($provider, 'boot')) {
                    $provider->boot();
                }
            });
    }
}
