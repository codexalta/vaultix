<?php

namespace Codexalta\Vaultix;

use Illuminate\Support\ServiceProvider;
use Codexalta\Vaultix\Commands\VaultixBackupCommand;

class VaultixServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vaultix.php', 'vaultix');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/vaultix.php' => config_path('vaultix.php'),
            ], 'vaultix-config');

            $this->publishes([
                __DIR__.'/../resources/views/emails' => resource_path('views/vendor/vaultix/emails'),
            ], 'vaultix-emails');
        }

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'vaultix');

        $this->registerGoogleDriver();
        $this->registerMiddleware();

        $this->commands([
            VaultixBackupCommand::class,
            \Codexalta\Vaultix\Commands\VaultixPruneLogsCommand::class,
            \Codexalta\Vaultix\Commands\VaultixInstallCommand::class,
        ]);

        // Automatically register the command in the scheduler
        $this->callAfterResolving(\Illuminate\Console\Scheduling\Schedule::class, function (\Illuminate\Console\Scheduling\Schedule $schedule) {
            $schedule->command('vaultix:run')->everyMinute();
            $schedule->command('vaultix:prune-logs')->daily();
        });
    }

    protected function registerMiddleware()
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('vaultix.auth', \Codexalta\Vaultix\Http\Middleware\VaultixAuthorization::class);
    }

    protected function registerGoogleDriver()
    {
        try {
            if (class_exists(\Masbug\Flysystem\GoogleDriveAdapter::class)) {
                \Illuminate\Support\Facades\Storage::extend('google', function ($app, $config) {
                    $options = [];
                    if (!empty($config['teamDriveId'] ?? null)) {
                        $options['teamDriveId'] = $config['teamDriveId'];
                    }

                    $client = new \Google\Client();
                    $client->setClientId($config['clientId'] ?? '');
                    $client->setClientSecret($config['clientSecret'] ?? '');
                    $client->refreshToken($config['refreshToken'] ?? '');

                    $service = new \Google\Service\Drive($client);
                    $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folderId'] ?? '/', $options);
                    $driver = new \League\Flysystem\Filesystem($adapter);

                    return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
                });
            }
        } catch (\Exception $e) {
            // Silently fail to avoid crashing the app
        }
    }
}
