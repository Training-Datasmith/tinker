<?php

declare (strict_types=1);
namespace Laravel\Tinker;

use Illuminate\Contracts\Support\Deferrable_Provider;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Service_Provider;
use Laravel\Lumen\Application as LumenApplication;
use Laravel\Tinker\Console\Tinker_Command;
class Tinker_Service_Provider extends Service_Provider implements Deferrable_Provider
{
    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        $source = realpath($raw = __DIR__ . '/../config/tinker.php') ?: $raw;
        if ($this->app instanceof Laravel_Application && $this->app->running_in_console()) {
            $this->publishes([$source => $this->app->config_path('tinker.php')]);
        } elseif ($this->app instanceof Lumen_Application) {
            $this->app->configure('tinker');
        }
        $this->merge_config_from($source, 'tinker');
    }
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('command.tinker', function (): \Laravel\Tinker\Console\Tinker_Command {
            return new Tinker_Command();
        });
        $this->commands(['command.tinker']);
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.tinker'];
    }
}