<?php namespace dam1r89\AssetOptimization;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\HTML as HTML;
use Illuminate\View\FileViewFinder as FileViewFinder;

class AssetOptimizationServiceProvider extends ServiceProvider {


    /**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{



        $this->app['command.asopt'] = $this->app->share(function($app)
        {
            $paths = $app['config']['view.paths'];
            return new AssetOptimizationCommand(new FileViewFinder($app['files'], $paths));
        });

        $this->commands('command.asopt');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
