<?php namespace Netson\L4shell;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class L4shellServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot ()
    {
        $this->package('netson/l4shell');

        // include custom exceptions
        include_once __DIR__ . '/Exceptions.php';

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register ()
    {
        // register L4shell
        $this->app['l4shell'] = $this->app->share(function($app) {
                    return new Command();
                });

        // Shortcut so developers don't need to add an Alias in app/config/app.php
        $this->app->booting(function() {
                    $loader = AliasLoader::getInstance();
                    $loader->alias('L4shell', 'Netson\L4shell\Facades\Command');
                });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides ()
    {
        return array('L4shell');

    }

}