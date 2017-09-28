<?php

namespace PragmaRX\TestsWatcher\Vendor\Laravel;

use Event;
use Illuminate\Support\Facades\Route;
use PragmaRX\TestsWatcher\Events\TestsFailed;
use PragmaRX\TestsWatcher\Listeners\Notify;
use PragmaRX\TestsWatcher\Support\Notifier;
use PragmaRX\TestsWatcher\Vendor\Laravel\Console\Commands\ClearCommand;
use PragmaRX\TestsWatcher\Vendor\Laravel\Console\Commands\TestCommand;
use PragmaRX\TestsWatcher\Vendor\Laravel\Console\Commands\WatchCommand;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot Service Provider.
     *
     */
    public function boot()
    {
        $this->publishConfiguration();

        $this->loadMigrations();

        $this->loadRoutes();

        $this->loadViews();
    }

    /**
     * Configure migrations path.
     *
     */
    private function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../migrations');
    }

    /**
     * Configure views path.
     *
     */
    private function loadViews()
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'pragmarx/ci');
    }

    /**
     * Configure config path.
     *
     */
    private function publishConfiguration()
    {
        $this->publishes([
            __DIR__.'/../../config/ci.php' => config_path('ci.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (! defined('CI_PATH')) {
            define('CI_PATH', realpath(__DIR__.'/../../../'));
        }

        $this->registerResourceWatcher();

        $this->registerService();

	    $this->registerWatcher();

	    $this->registerTester();

	    $this->registerWatchCommand();

	    $this->registerTestCommand();

        $this->registerClearCommand();

        $this->registerNotifier();

        $this->registerEventListeners();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['ci'];
    }

    /**
     * Register the clear command.
     *
     */
    private function registerClearCommand()
    {
        $this->app->singleton('ci.clear.command', function($app)
        {
            return new ClearCommand();
        });

        $this->commands('ci.clear.command');
    }

    /**
     * Register event listeners.
     *
     */
    private function registerEventListeners()
    {
        Event::listen(TestsFailed::class, Notify::class);
    }

    /**
     * Register the watch command.
     *
     */
    private function registerNotifier()
    {
        $this->app->singleton('ci.notifier', function()
        {
            return new Notifier();
        });
    }

    /**
     * Register the watch command.
     *
     */
    private function registerWatchCommand()
	{
        $this->app->singleton('ci.watch.command', function($app)
        {
            return new WatchCommand();
        });

		$this->commands('ci.watch.command');
	}

    /**
     * Register the test command.
     *
     */
    private function registerTestCommand()
	{
        $this->app->singleton('ci.test.command', function()
        {
            return new TestCommand();
        });

		$this->commands('ci.test.command');
	}

    /**
     * Register service service.
     *
     */
    private function registerService()
    {
        $this->app->singleton('ci', function($app)
        {
            $service = $this->app->make('PragmaRX\TestsWatcher\Service');

            $service->setConfig(config('ci'));

            return $service;
        });
    }

    /**
     * Register service watcher.
     *
     */
    private function registerWatcher()
	{
		$this->app->singleton('ci.watcher', function($app)
		{
			$watcher = $this->app->make('PragmaRX\TestsWatcher\Services\Watcher');

			$watcher->setConfig(config('ci'));

			return $watcher;
		});
	}

    /**
     * Register service tester.
     *
     */
    private function registerTester()
	{
		$this->app->singleton('ci.tester', function($app)
		{
			$tester = $this->app->make('PragmaRX\TestsWatcher\Services\Tester');

			$tester->setConfig(config('ci'));

			return $tester;
		});
	}

    /**
     * Register the resource watcher.
     *
     */
    private function registerResourceWatcher()
	{
		$this->app->register('JasonLewis\ResourceWatcher\Integration\LaravelServiceProvider');
	}

    /**
     * Register all routes.
     *
     */
    private function loadRoutes()
	{
        Route::group([
            'prefix' => '/tests-watcher',
            'namespace' => 'PragmaRX\TestsWatcher\Vendor\Laravel\Http\Controllers',
            'middleware' => 'web',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        });
	}

	/**
	 * Get the root directory for this ServiceProvider
	 *
	 * @return string
	 */
	public function getRootDirectory()
	{
		return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..';
	}
}
