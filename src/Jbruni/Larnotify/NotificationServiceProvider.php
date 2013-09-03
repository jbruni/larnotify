<?php namespace Jbruni\Larnotify;

use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Notification Manager
	 *
	 * @var Jbruni\Larnotify\NotificationManager
	 */
	protected $manager;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('jbruni/larnotify');
		$this->registerNotifyEvents();
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$manager = $this->manager = new NotificationManager($this->app);

		$this->app['larnotify'] = $this->app->share(function() use ($manager)
		{
			return $manager;
		});
	}

	/**
	 * Register the events needed for notification.
	 *
	 * @return void
	 */
	protected function registerNotifyEvents()
	{
		$app = $this->app;
		$manager = $this->manager;

		$app->before(function($request) use ($app, $manager)
		{
			$app->make('view')->share($app->make('config')->get('larnotify::view_share'), $manager);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('larnotify');
	}

}
